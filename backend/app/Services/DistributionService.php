<?php

namespace App\Services;

use App\Enums\BankTransferStatus;
use App\Enums\DistributionReservationStatus;
use App\Enums\DistributionSourceType;
use App\Enums\DistributionStatus;
use App\Enums\DistributionType;
use App\Exceptions\ZakatException;
use App\Models\AccountingEvent;
use App\Models\Assessment;
use App\Models\Distribution;
use App\Models\DistributionBankTransfer;
use App\Models\DistributionCashDetail;
use App\Models\DistributionConfirmation;
use App\Models\DistributionItem;
use App\Models\DistributionProof;
use App\Models\DistributionRequest;
use App\Models\DistributionReservation;
use App\Models\DistributionSchedule;
use App\Models\Fund;
use App\Models\FundReservation;
use App\Models\Mustahik;
use App\Models\Program;
use App\Models\ProgramBudgetCommitment;
use App\Models\ProgramEnrollment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * PRD 12 — Distribution.
 *
 * Distribution tidak pernah menyentuh saldo atau ledger secara langsung
 * (PRD 12B §4): pergerakan dana lewat FundService dan pencatatan akuntansi
 * lewat accounting event yang diproses modul Accounting.
 */
class DistributionService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly FundService $funds,
        private readonly ProgramService $programs,
        private readonly AccountingService $accounting,
    ) {}

    // ------------------------------------------------------------------ query

    public function list(array $filters): LengthAwarePaginator
    {
        return Distribution::with(['mustahik:id,display_name,mustahik_number', 'fund:id,name', 'items', 'activeReservation'])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['distribution_type'] ?? null, fn ($q, $v) => $q->where('distribution_type', $v))
            ->when($filters['program_id'] ?? null, fn ($q, $v) => $q->where('program_id', $v))
            ->when($filters['mustahik_id'] ?? null, fn ($q, $v) => $q->where('mustahik_id', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('distribution_number', 'ilike', "%{$v}%"))
            ->latest()
            ->paginate($this->perPage($filters));
    }

    public function find(string $id): Distribution
    {
        return Distribution::with(['mustahik', 'fund', 'program:id,name,program_code', 'items', 'reservations', 'cashDetails', 'bankTransfers', 'schedules', 'proofs', 'confirmation'])
            ->find($id) ?? throw ZakatException::notFound('Distribution tidak ditemukan.');
    }

    // ----------------------------------------------------------------- create

    public function create(array $data): Distribution
    {
        $context = $this->validateRequest($data);

        return DB::transaction(function () use ($data, $context) {
            $distribution = new Distribution;
            $distribution->fill($data);
            $distribution->distribution_number = app(BusinessNumberService::class)->next('DST');
            $distribution->source_type = $data['source_type'] ?? DistributionSourceType::Direct->value;
            $distribution->currency = $context['fund']->currency;
            $distribution->approved_amount = 0;
            $distribution->distributed_amount = 0;
            $distribution->status = DistributionStatus::Draft;
            $distribution->created_by = auth()->id();
            $distribution->save();

            foreach ($data['items'] ?? [] as $item) {
                DistributionItem::create($item + [
                    'distribution_id' => $distribution->id,
                    'total_value' => bcmul((string) ($item['quantity'] ?? 1), (string) ($item['unit_value'] ?? 0), 2),
                ]);
            }

            $this->audit->record('distribution_created', $distribution);

            return $this->find($distribution->id);
        });
    }

    public function update(Distribution $distribution, array $data): Distribution
    {
        $this->assertStatus($distribution, DistributionStatus::Draft);

        $distribution->fill($data)->save();
        $this->audit->record('distribution_updated', $distribution);

        return $this->find($distribution->id);
    }

    // ------------------------------------------------------------- lifecycle

    public function submit(Distribution $distribution): Distribution
    {
        $this->assertTransition($distribution, DistributionStatus::PendingApproval);

        // Kelayakan divalidasi ulang saat submit karena saldo dan status entitas
        // terkait bisa berubah setelah draft dibuat.
        $this->validateRequest($this->snapshot($distribution));

        return $this->transition($distribution, DistributionStatus::PendingApproval, 'distribution_submitted', [
            'submitted_by' => auth()->id(),
            'submitted_at' => now(),
        ]);
    }

    public function approve(Distribution $distribution): Distribution
    {
        $this->assertTransition($distribution, DistributionStatus::Approved);

        // PRD 12I §24 — maker checker.
        if ($distribution->created_by !== null && $distribution->created_by === auth()->id()) {
            throw ZakatException::forbidden('Maker tidak dapat menyetujui distribution sendiri.');
        }

        return DB::transaction(function () use ($distribution) {
            $approved = (string) $distribution->requested_amount;

            // PRD 12Y §58 — komitmen budget program dipesan saat approval.
            if ($distribution->program_id !== null) {
                $program = Program::find($distribution->program_id) ?? throw ZakatException::notFound('Program tidak ditemukan.');
                $budget = $program->budgets()->whereIn('status', ['draft', 'active'])->first()
                    ?? throw ZakatException::conflict('Program belum memiliki budget aktif.');

                $this->programs->commit($program, [
                    'program_budget_id' => $budget->id,
                    'distribution_id' => $distribution->id,
                    'enrollment_id' => $distribution->program_enrollment_id,
                    'amount' => $approved,
                    'currency' => $distribution->currency,
                    'reason' => $distribution->distribution_number,
                ]);
            }

            return $this->transition($distribution, DistributionStatus::Approved, 'distribution_approved', [
                'approved_amount' => $approved,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        });
    }

    /** PRD 12AB — distribution.reject mengembalikan distribution ke draft. */
    public function reject(Distribution $distribution, string $reason): Distribution
    {
        $this->assertStatus($distribution, DistributionStatus::PendingApproval);

        return $this->transition($distribution, DistributionStatus::Draft, 'distribution_rejected', [
            'rejection_reason' => $reason,
        ], ['reason' => $reason]);
    }

    /** PRD 12H — menahan dana agar tidak bisa dipakai distribution lain. */
    public function reserve(Distribution $distribution): Distribution
    {
        $this->assertTransition($distribution, DistributionStatus::Reserved);

        return DB::transaction(function () use ($distribution) {
            $fundReservation = $this->funds->reservation($distribution->fund, [
                'amount' => $distribution->approved_amount,
                'target_type' => 'distribution',
                'target_id' => $distribution->id,
                'reason' => 'Reservation '.$distribution->distribution_number,
            ]);

            DistributionReservation::create([
                'distribution_id' => $distribution->id,
                'fund_id' => $distribution->fund_id,
                'fund_reservation_id' => $fundReservation->id,
                'reserved_amount' => $distribution->approved_amount,
                'currency' => $distribution->currency,
                'reserved_at' => now(),
                'status' => DistributionReservationStatus::Active,
            ]);

            return $this->transition($distribution, DistributionStatus::Reserved, 'distribution_reserved');
        });
    }

    public function schedule(Distribution $distribution, array $data = []): Distribution
    {
        $this->assertTransition($distribution, DistributionStatus::Scheduled);

        return DB::transaction(function () use ($distribution, $data) {
            DistributionSchedule::create([
                'distribution_id' => $distribution->id,
                'schedule_type' => $data['schedule_type'] ?? 'one_time',
                'scheduled_date' => $data['scheduled_date'] ?? today(),
                'amount' => $data['amount'] ?? $distribution->approved_amount,
                'status' => 'pending',
            ]);

            return $this->transition($distribution, DistributionStatus::Scheduled, 'distribution_scheduled', [
                'scheduled_date' => $data['scheduled_date'] ?? $distribution->scheduled_date ?? today(),
            ]);
        });
    }

    public function process(Distribution $distribution): Distribution
    {
        $this->assertTransition($distribution, DistributionStatus::Processing);

        $attributes = $distribution->status === DistributionStatus::Failed
            ? ['retry_count' => $distribution->retry_count + 1, 'failure_reason' => null, 'failure_note' => null, 'failed_at' => null]
            : [];

        $event = $distribution->status === DistributionStatus::Failed ? 'distribution_retried' : 'distribution_processing';

        return $this->transition($distribution, DistributionStatus::Processing, $event, $attributes);
    }

    /**
     * PRD 12O §37 — realisasi penuh atau sebagian. Pergerakan dana, konsumsi
     * reservation, update budget program, dan accounting event terjadi atomik.
     */
    public function complete(Distribution $distribution, array $data): Distribution
    {
        $this->assertStatus($distribution, DistributionStatus::Processing);

        $amount = (string) ($data['amount'] ?? $distribution->remainingAmount());

        if (bccomp($amount, '0', 2) <= 0) {
            throw ZakatException::conflict('Nominal realisasi harus lebih besar dari nol.');
        }

        $total = bcadd((string) $distribution->distributed_amount, $amount, 2);

        if (bccomp($total, (string) $distribution->approved_amount, 2) > 0) {
            throw ZakatException::conflict('Distributed amount melebihi approved amount.');
        }

        $this->assertRealisationDetail($distribution, $data);

        return DB::transaction(function () use ($distribution, $data, $amount, $total) {
            $isFinal = bccomp($total, (string) $distribution->approved_amount, 2) === 0;
            $remaining = bcsub((string) $distribution->approved_amount, $total, 2);

            // Reservation dilepas lebih dulu: dana yang ditahan untuk distribution
            // ini tidak boleh menghalangi outflow-nya sendiri. Sisa yang belum
            // direalisasikan langsung ditahan kembali.
            $this->consumeReservation($distribution, $remaining);

            $this->funds->outflow($distribution->fund, [
                'amount' => $amount,
                'movement_type' => 'distribution',
                'source_type' => 'distribution',
                'source_id' => $distribution->id,
                'description' => $distribution->distribution_number,
            ]);

            $this->recordRealisationDetail($distribution, $data, $amount);

            if ($isFinal) {
                $commitment = ProgramBudgetCommitment::where('distribution_id', $distribution->id)->where('status', 'committed')->first();

                if ($commitment !== null) {
                    $this->programs->disburse($commitment);
                }
            }

            $status = $isFinal ? DistributionStatus::Completed : DistributionStatus::PartiallyCompleted;

            $distribution = $this->transition($distribution, $status, $isFinal ? 'distribution_completed' : 'distribution_partially_completed', [
                'distributed_amount' => $total,
                'distribution_date' => $data['distribution_date'] ?? today(),
            ]);

            if ($isFinal) {
                $this->emitAccountingEvent($distribution, 'DISTRIBUTIONCOMPLETED', $total);
            }

            return $this->find($distribution->id);
        });
    }

    /** PRD 12T §49 — kegagalan melepas reservation agar dana kembali tersedia. */
    public function fail(Distribution $distribution, array $data): Distribution
    {
        $this->assertTransition($distribution, DistributionStatus::Failed);

        return DB::transaction(function () use ($distribution, $data) {
            $this->releaseReservation($distribution, 'Distribution failed: '.$data['failure_reason']);

            return $this->transition($distribution, DistributionStatus::Failed, 'distribution_failed', [
                'failure_reason' => $data['failure_reason'],
                'failure_note' => $data['failure_note'] ?? null,
                'failed_at' => now(),
            ], ['failure_reason' => $data['failure_reason']]);
        });
    }

    /** PRD 12U §50. */
    public function cancel(Distribution $distribution, string $reason): Distribution
    {
        $this->assertTransition($distribution, DistributionStatus::Cancelled);

        return DB::transaction(function () use ($distribution, $reason) {
            $this->releaseReservation($distribution, $reason);
            $this->releaseCommitment($distribution, $reason);

            return $this->transition($distribution, DistributionStatus::Cancelled, 'distribution_cancelled', [
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ], ['reason' => $reason]);
        });
    }

    /** PRD 12V §52 — dana kembali dan accounting reversal event dibuat. */
    public function reverse(Distribution $distribution, string $reason): Distribution
    {
        $this->assertTransition($distribution, DistributionStatus::Reversed);

        $returned = (string) $distribution->distributed_amount;

        return DB::transaction(function () use ($distribution, $reason, $returned) {
            if (bccomp($returned, '0', 2) > 0) {
                $this->funds->adjust([
                    'fund_id' => $distribution->fund_id,
                    'adjustment_type' => 'increase',
                    'amount' => $returned,
                    'reason' => 'Reversal '.$distribution->distribution_number.': '.$reason,
                ]);
            }

            $this->releaseReservation($distribution, $reason);
            $this->releaseCommitment($distribution, $reason);

            $distribution = $this->transition($distribution, DistributionStatus::Reversed, 'distribution_reversed', [
                'reversed_by' => auth()->id(),
                'reversed_at' => now(),
                'reversal_reason' => $reason,
            ], ['reason' => $reason, 'returned_amount' => $returned]);

            $this->emitAccountingEvent($distribution, 'DISTRIBUTIONREVERSED', $returned, ['reason' => $reason]);

            return $this->find($distribution->id);
        });
    }

    // ---------------------------------------------------- proof and confirmation

    /** PRD 12R §43. */
    public function addProof(Distribution $distribution, array $data): DistributionProof
    {
        $proof = DistributionProof::create($data + [
            'distribution_id' => $distribution->id,
            'uploaded_by' => auth()->id(),
        ]);

        $this->audit->record('distribution_proof_uploaded', $distribution, context: ['proof_id' => $proof->id, 'proof_type' => $data['proof_type']]);

        return $proof;
    }

    public function verifyProof(DistributionProof $proof): DistributionProof
    {
        if ($proof->verified_at !== null) {
            throw ZakatException::invalidTransition('Bukti sudah diverifikasi.');
        }

        $proof->forceFill(['verified_by' => auth()->id(), 'verified_at' => now()])->save();
        $this->audit->record('distribution_proof_verified', $proof->distribution_id ? $this->find($proof->distribution_id) : null, context: ['proof_id' => $proof->id]);

        return $proof;
    }

    /** PRD 12S §46 — konfirmasi penerimaan oleh mustahik. */
    public function confirm(Distribution $distribution, array $data): DistributionConfirmation
    {
        if (! in_array($distribution->status, [DistributionStatus::Completed, DistributionStatus::PartiallyCompleted], true)) {
            throw ZakatException::invalidTransition('Konfirmasi hanya untuk distribution yang sudah direalisasikan.');
        }

        if ($distribution->confirmation()->exists()) {
            throw ZakatException::duplicate('Distribution ini sudah dikonfirmasi penerima.');
        }

        $confirmation = DistributionConfirmation::create([
            'distribution_id' => $distribution->id,
            'confirmation_method' => $data['confirmation_method'],
            'confirmed_at' => $data['confirmed_at'] ?? now(),
            'confirmed_by' => auth()->id(),
            'confirmation_data' => $data['confirmation_data'] ?? null,
            'status' => 'confirmed',
        ]);

        $this->audit->record('distribution_recipient_confirmed', $distribution, context: ['method' => $data['confirmation_method']]);

        return $confirmation;
    }

    // --------------------------------------------------------------- requests

    public function requests(array $filters): LengthAwarePaginator
    {
        return DistributionRequest::with(['mustahik:id,display_name,mustahik_number', 'fund:id,name'])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate($this->perPage($filters));
    }

    public function findRequest(string $id): DistributionRequest
    {
        return DistributionRequest::with(['mustahik', 'fund', 'distribution'])->find($id)
            ?? throw ZakatException::notFound('Distribution request tidak ditemukan.');
    }

    public function createRequest(array $data): DistributionRequest
    {
        $this->validateRequest($data);

        $request = new DistributionRequest;
        $request->fill($data);
        $request->request_number = app(BusinessNumberService::class)->next('DSR');
        $request->requested_by = auth()->id();
        $request->requested_at = now();
        $request->status = 'pending';
        $request->save();

        $this->audit->record('distribution_request_created', $request);

        return $this->findRequest($request->id);
    }

    /** Approval request langsung membentuk Distribution draft (PRD 12F §14). */
    public function approveRequest(DistributionRequest $request): DistributionRequest
    {
        if ($request->status !== 'pending') {
            throw ZakatException::invalidTransition('Request tidak menunggu approval.');
        }

        return DB::transaction(function () use ($request) {
            $distribution = $this->create([
                'mustahik_id' => $request->mustahik_id,
                'program_id' => $request->program_id,
                'assessment_id' => $request->assessment_id,
                'fund_id' => $request->fund_id,
                'distribution_type' => $request->distribution_type->value,
                'source_type' => $request->program_id !== null ? DistributionSourceType::Program->value : DistributionSourceType::Direct->value,
                'requested_amount' => $request->requested_amount,
                'priority' => $request->priority,
                'description' => $request->reason,
            ]);

            $request->forceFill([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'distribution_id' => $distribution->id,
            ])->saveQuietly();

            $this->audit->record('distribution_request_approved', $request, context: ['distribution_id' => $distribution->id]);

            return $this->findRequest($request->id);
        });
    }

    public function rejectRequest(DistributionRequest $request, string $reason): DistributionRequest
    {
        if ($request->status !== 'pending') {
            throw ZakatException::invalidTransition('Request tidak menunggu approval.');
        }

        $request->forceFill([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ])->saveQuietly();

        $this->audit->record('distribution_request_rejected', $request, context: ['reason' => $reason]);

        return $this->findRequest($request->id);
    }

    // -------------------------------------------------------------- validation

    /**
     * PRD 12G §17 — validasi kelayakan sebelum dana dipesan.
     *
     * @return array{fund: Fund, mustahik: Mustahik}
     */
    public function validateRequest(array $data): array
    {
        $mustahik = Mustahik::find($data['mustahik_id']) ?? throw ZakatException::notFound('Mustahik tidak ditemukan.');

        if ($mustahik->status !== 'active') {
            throw ZakatException::conflict('Mustahik tidak aktif.');
        }

        $fund = Fund::find($data['fund_id']) ?? throw ZakatException::notFound('Fund tidak ditemukan.');

        if ($fund->status?->value !== 'active') {
            throw ZakatException::conflict('Fund tidak aktif.');
        }

        $amount = (string) $data['requested_amount'];
        $availability = $this->funds->availability($fund, $amount);

        if (! $availability['available']) {
            throw ZakatException::conflict('Saldo fund tidak mencukupi untuk nominal yang diminta.');
        }

        if (! empty($data['assessment_id'])) {
            $assessment = Assessment::find($data['assessment_id']) ?? throw ZakatException::notFound('Assessment tidak ditemukan.');

            if ($assessment->mustahik_id !== $mustahik->id) {
                throw ZakatException::conflict('Assessment bukan milik mustahik tersebut.');
            }

            if (! in_array((string) $assessment->status, ['approved', 'completed'], true)) {
                throw ZakatException::conflict('Assessment belum disetujui.');
            }
        }

        if (! empty($data['program_id'])) {
            $program = Program::find($data['program_id']) ?? throw ZakatException::notFound('Program tidak ditemukan.');

            if ((string) $program->status !== 'active') {
                throw ZakatException::conflict('Program harus active.');
            }

            if (! empty($data['program_enrollment_id'])) {
                $enrollment = ProgramEnrollment::where('program_id', $program->id)->find($data['program_enrollment_id'])
                    ?? throw ZakatException::notFound('Program enrollment tidak ditemukan.');

                if ($enrollment->mustahik_id !== $mustahik->id) {
                    throw ZakatException::conflict('Enrollment bukan milik mustahik tersebut.');
                }

                if ((string) $enrollment->status !== 'approved') {
                    throw ZakatException::conflict('Enrollment belum disetujui.');
                }
            }
        }

        return ['fund' => $fund, 'mustahik' => $mustahik];
    }

    // ----------------------------------------------------------------- helpers

    /** Bentuk array yang dipahami validateRequest dari distribution tersimpan. */
    private function snapshot(Distribution $distribution): array
    {
        return [
            'mustahik_id' => $distribution->mustahik_id,
            'fund_id' => $distribution->fund_id,
            'program_id' => $distribution->program_id,
            'program_enrollment_id' => $distribution->program_enrollment_id,
            'assessment_id' => $distribution->assessment_id,
            'requested_amount' => $distribution->requested_amount,
        ];
    }

    /** PRD 12L §30 dan 12M — realisasi cash dan transfer wajib punya detail. */
    private function assertRealisationDetail(Distribution $distribution, array $data): void
    {
        if ($distribution->distribution_type === DistributionType::BankTransfer && empty($data['bank_transfer'])) {
            throw ZakatException::conflict('Distribution bank transfer membutuhkan detail rekening tujuan.');
        }
    }

    private function recordRealisationDetail(Distribution $distribution, array $data, string $amount): void
    {
        if ($distribution->distribution_type === DistributionType::Cash) {
            DistributionCashDetail::create([
                'distribution_id' => $distribution->id,
                'amount' => $amount,
                'currency' => $distribution->currency,
                'cashier_id' => auth()->id(),
                'disbursed_at' => $data['distribution_date'] ?? now(),
                'receipt_number' => $data['receipt_number'] ?? null,
            ]);
        }

        if ($distribution->distribution_type === DistributionType::BankTransfer) {
            $bank = $data['bank_transfer'];

            $transfer = new DistributionBankTransfer;
            $transfer->fill([
                'distribution_id' => $distribution->id,
                'bank_name' => $bank['bank_name'],
                'account_holder_name' => $bank['account_holder_name'],
                'transfer_reference' => $bank['transfer_reference'] ?? null,
                'transfer_amount' => $amount,
                'transfer_date' => $data['distribution_date'] ?? today(),
                'status' => BankTransferStatus::Success,
            ]);
            // Nomor rekening sengaja tidak fillable: hanya boleh diisi di sini,
            // tidak pernah lewat mass assignment dari payload.
            $transfer->account_number_encrypted = $bank['account_number'];
            $transfer->account_number_masked = DistributionBankTransfer::mask($bank['account_number']);
            $transfer->save();
        }
    }

    /**
     * PRD 12W §54 — reservation dikonsumsi saat dana benar keluar.
     *
     * Dipanggil sebelum outflow. Bila masih ada sisa approved amount yang belum
     * direalisasikan (PRD 12O), sisa itu ditahan kembali lewat reservation baru
     * supaya dana tidak bisa dipakai distribution lain.
     */
    private function consumeReservation(Distribution $distribution, string $remaining): void
    {
        $reservation = $distribution->activeReservation()->first();

        if ($reservation === null) {
            return;
        }

        $fundReservation = $reservation->fund_reservation_id !== null
            ? FundReservation::find($reservation->fund_reservation_id)
            : null;

        if ($fundReservation !== null && $fundReservation->status === 'active') {
            $this->funds->releaseReservation($fundReservation, 'Dikonsumsi oleh '.$distribution->distribution_number);
        }

        $reservation->forceFill(['status' => DistributionReservationStatus::Consumed, 'released_at' => now()])->save();

        if (bccomp($remaining, '0', 2) > 0) {
            $next = $this->funds->reservation($distribution->fund, [
                'amount' => $remaining,
                'target_type' => 'distribution',
                'target_id' => $distribution->id,
                'reason' => 'Sisa reservation '.$distribution->distribution_number,
            ]);

            DistributionReservation::create([
                'distribution_id' => $distribution->id,
                'fund_id' => $distribution->fund_id,
                'fund_reservation_id' => $next->id,
                'reserved_amount' => $remaining,
                'currency' => $distribution->currency,
                'reserved_at' => now(),
                'status' => DistributionReservationStatus::Active,
            ]);
        }
    }

    private function releaseReservation(Distribution $distribution, string $reason): void
    {
        $reservation = $distribution->activeReservation()->first();

        if ($reservation === null) {
            return;
        }

        $fundReservation = $reservation->fund_reservation_id !== null
            ? FundReservation::find($reservation->fund_reservation_id)
            : null;

        if ($fundReservation !== null && $fundReservation->status === 'active') {
            $this->funds->releaseReservation($fundReservation, $reason);
        }

        $reservation->forceFill(['status' => DistributionReservationStatus::Released, 'released_at' => now()])->save();
        $this->audit->record('distribution_reservation_released', $distribution, context: ['reason' => $reason]);
    }

    private function releaseCommitment(Distribution $distribution, string $reason): void
    {
        $commitment = ProgramBudgetCommitment::where('distribution_id', $distribution->id)
            ->whereIn('status', ['committed', 'disbursed'])
            ->first();

        if ($commitment !== null) {
            $this->programs->releaseCommitment($commitment, $reason);
        }
    }

    /**
     * PRD 12X §56 dan §57. Distribution hanya menerbitkan event; jurnal dibuat
     * modul Accounting berdasarkan rule. Unique index pada accounting_events
     * membuat penerbitan event ini idempoten.
     */
    private function emitAccountingEvent(Distribution $distribution, string $eventType, string $amount, array $extra = []): void
    {
        $exists = AccountingEvent::where('event_type', $eventType)
            ->where('source_type', 'distribution')
            ->where('source_id', $distribution->id)
            ->exists();

        if ($exists) {
            return;
        }

        $this->accounting->event([
            'event_type' => $eventType,
            'source_type' => 'distribution',
            'source_id' => $distribution->id,
            'reference_number' => $distribution->distribution_number,
            'event_date' => $distribution->distribution_date ?? today(),
            'payload' => $extra + [
                'fund_id' => $distribution->fund_id,
                'mustahik_id' => $distribution->mustahik_id,
                'program_id' => $distribution->program_id,
                'distribution_type' => $distribution->distribution_type->value,
                'amount' => $amount,
                'currency' => $distribution->currency,
            ],
        ]);
    }

    private function transition(Distribution $distribution, DistributionStatus $status, string $event, array $attributes = [], array $context = []): Distribution
    {
        $previous = $distribution->status;

        $distribution->forceFill($attributes + ['status' => $status])->saveQuietly();

        $this->audit->record($event, $distribution, ['status' => $previous->value], ['status' => $status->value], $context);

        return $distribution;
    }

    private function assertStatus(Distribution $distribution, DistributionStatus $status): void
    {
        if ($distribution->status !== $status) {
            throw ZakatException::invalidTransition("Distribution harus berstatus {$status->value}, saat ini {$distribution->status->value}.");
        }
    }

    private function assertTransition(Distribution $distribution, DistributionStatus $next): void
    {
        if (! $distribution->status->canTransitionTo($next)) {
            throw ZakatException::invalidTransition("Distribution berstatus {$distribution->status->value} tidak dapat berpindah ke {$next->value}.");
        }
    }

    private function perPage(array $filters): int
    {
        return min((int) ($filters['per_page'] ?? 15), (int) config('zakat.pagination.max_per_page'));
    }
}
