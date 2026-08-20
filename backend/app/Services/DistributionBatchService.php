<?php

namespace App\Services;

use App\Enums\DistributionBatchStatus;
use App\Enums\DistributionSourceType;
use App\Enums\DistributionStatus;
use App\Enums\ErrorCode;
use App\Exceptions\ZakatException;
use App\Models\Distribution;
use App\Models\DistributionBatch;
use App\Models\DistributionBeneficiary;
use App\Models\Fund;
use App\Models\FundReservation;
use App\Models\Program;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/** PRD 12P — batch distribution untuk banyak mustahik sekaligus. */
class DistributionBatchService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly FundService $funds,
        private readonly ProgramService $programs,
        private readonly DistributionService $distributions,
    ) {}

    public function list(array $filters): LengthAwarePaginator
    {
        return DistributionBatch::with(['fund:id,name', 'program:id,name'])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('batch_number', 'ilike', "%{$v}%")->orWhere('name', 'ilike', "%{$v}%"))
            ->latest()
            ->paginate(min((int) ($filters['per_page'] ?? 15), (int) config('zakat.pagination.max_per_page')));
    }

    public function find(string $id): DistributionBatch
    {
        return DistributionBatch::with(['fund', 'program:id,name', 'beneficiaries.mustahik:id,display_name,mustahik_number'])
            ->find($id) ?? throw ZakatException::notFound('Batch tidak ditemukan.');
    }

    public function create(array $data): DistributionBatch
    {
        $fund = Fund::find($data['fund_id']) ?? throw ZakatException::notFound('Fund tidak ditemukan.');

        if ($fund->status?->value !== 'active') {
            throw ZakatException::conflict('Fund tidak aktif.');
        }

        if (! empty($data['program_id'])) {
            $program = Program::find($data['program_id']) ?? throw ZakatException::notFound('Program tidak ditemukan.');

            if ((string) $program->status !== 'active') {
                throw ZakatException::conflict('Program harus active.');
            }
        }

        $batch = new DistributionBatch;
        $batch->fill($data);
        $batch->batch_number = app(BusinessNumberService::class)->next('DTB');
        $batch->status = DistributionBatchStatus::Draft;
        $batch->total_amount = 0;
        $batch->total_beneficiary = 0;
        $batch->created_by = auth()->id();
        $batch->save();

        $this->audit->record('distribution_batch_created', $batch);

        return $this->find($batch->id);
    }

    /** PRD 12P §41 — beneficiary hanya boleh ditambah selama batch masih disusun. */
    public function addBeneficiary(DistributionBatch $batch, array $data): DistributionBeneficiary
    {
        $this->assertEditable($batch);

        $exists = DistributionBeneficiary::where('batch_id', $batch->id)->where('mustahik_id', $data['mustahik_id'])->exists();

        if ($exists) {
            throw ZakatException::duplicate('Mustahik sudah terdaftar pada batch ini.');
        }

        return DB::transaction(function () use ($batch, $data) {
            $beneficiary = DistributionBeneficiary::create([
                'batch_id' => $batch->id,
                'mustahik_id' => $data['mustahik_id'],
                'approved_amount' => $data['approved_amount'],
                'status' => 'pending',
            ]);

            $this->recalculate($batch);

            return $beneficiary;
        });
    }

    public function removeBeneficiary(DistributionBatch $batch, string $beneficiaryId): void
    {
        $this->assertEditable($batch);

        $beneficiary = DistributionBeneficiary::where('batch_id', $batch->id)->find($beneficiaryId)
            ?? throw ZakatException::notFound('Beneficiary tidak ditemukan.');

        DB::transaction(function () use ($batch, $beneficiary) {
            $beneficiary->delete();
            $this->recalculate($batch);
        });
    }

    /** PRD 12P §41 — validasi tiap beneficiary sebelum batch diajukan. */
    public function validateBatch(DistributionBatch $batch): DistributionBatch
    {
        $this->assertTransition($batch, DistributionBatchStatus::Validated);

        $beneficiaries = $batch->beneficiaries()->get();

        if ($beneficiaries->isEmpty()) {
            throw ZakatException::conflict('Batch belum memiliki beneficiary.');
        }

        $errors = [];

        foreach ($beneficiaries as $beneficiary) {
            try {
                $this->distributions->validateRequest([
                    'mustahik_id' => $beneficiary->mustahik_id,
                    'fund_id' => $batch->fund_id,
                    'program_id' => $batch->program_id,
                    'requested_amount' => $beneficiary->approved_amount,
                ]);

                $beneficiary->forceFill(['status' => 'validated', 'failure_reason' => null, 'failure_note' => null])->save();
            } catch (ZakatException $exception) {
                $beneficiary->forceFill(['status' => 'invalid', 'failure_reason' => 'verification_failed', 'failure_note' => $exception->getMessage()])->save();
                $errors[$beneficiary->mustahik_id] = [$exception->getMessage()];
            }
        }

        if ($errors !== []) {
            throw new ZakatException(ErrorCode::Conflict, 'Sebagian beneficiary tidak lolos validasi.', $errors);
        }

        // Saldo dicek terhadap total batch, bukan hanya per beneficiary.
        if (! $this->funds->availability($batch->fund, (string) $batch->total_amount)['available']) {
            throw ZakatException::conflict('Saldo fund tidak mencukupi untuk total batch.');
        }

        return $this->transition($batch, DistributionBatchStatus::Validated, 'distribution_batch_validated');
    }

    public function submit(DistributionBatch $batch): DistributionBatch
    {
        $this->assertTransition($batch, DistributionBatchStatus::PendingApproval);

        return $this->transition($batch, DistributionBatchStatus::PendingApproval, 'distribution_batch_submitted');
    }

    /** PRD 12P §41 — approval menahan dana untuk seluruh batch. */
    public function approve(DistributionBatch $batch): DistributionBatch
    {
        $this->assertTransition($batch, DistributionBatchStatus::Approved);

        if ($batch->created_by !== null && $batch->created_by === auth()->id()) {
            throw ZakatException::forbidden('Maker tidak dapat menyetujui batch sendiri.');
        }

        return DB::transaction(function () use ($batch) {
            $this->funds->reservation($batch->fund, [
                'amount' => $batch->total_amount,
                'target_type' => 'distribution_batch',
                'target_id' => $batch->id,
                'reason' => 'Reservation batch '.$batch->batch_number,
            ]);

            return $this->transition($batch, DistributionBatchStatus::Approved, 'distribution_batch_approved', [
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        });
    }

    /**
     * PRD 12P §41 — membuat dan merealisasikan satu Distribution per beneficiary.
     * Setiap beneficiary diproses dalam transaksi sendiri supaya satu kegagalan
     * tidak membatalkan penerima lain yang sudah berhasil.
     */
    public function process(DistributionBatch $batch): DistributionBatch
    {
        $this->assertTransition($batch, DistributionBatchStatus::Processing);

        $batch = $this->transition($batch, DistributionBatchStatus::Processing, 'distribution_batch_processing');

        // Reservation batch dilepas lebih dulu agar saldo tersedia untuk tiap outflow.
        $this->releaseBatchReservation($batch, 'Dikonsumsi oleh proses batch '.$batch->batch_number);

        $succeeded = 0;
        $failed = 0;

        foreach ($batch->beneficiaries()->whereIn('status', ['validated', 'pending', 'failed'])->get() as $beneficiary) {
            try {
                DB::transaction(function () use ($batch, $beneficiary) {
                    $distribution = $this->createBatchDistribution($batch, $beneficiary);

                    $this->distributions->complete($distribution, ['amount' => $beneficiary->approved_amount]);

                    $beneficiary->forceFill([
                        'distribution_id' => $distribution->id,
                        'distributed_amount' => $beneficiary->approved_amount,
                        'status' => 'completed',
                        'failure_reason' => null,
                        'failure_note' => null,
                    ])->save();
                });

                $succeeded++;
            } catch (\Throwable $exception) {
                $beneficiary->forceFill([
                    'status' => 'failed',
                    'failure_reason' => 'system_error',
                    'failure_note' => $exception->getMessage(),
                ])->save();

                $failed++;
            }
        }

        $status = $failed === 0 ? DistributionBatchStatus::Completed : DistributionBatchStatus::PartiallyCompleted;

        $batch = $this->transition($batch, $status, 'distribution_batch_processed', [], [
            'succeeded' => $succeeded,
            'failed' => $failed,
        ]);

        return $this->find($batch->id);
    }

    public function cancel(DistributionBatch $batch, string $reason): DistributionBatch
    {
        $this->assertTransition($batch, DistributionBatchStatus::Cancelled);

        return DB::transaction(function () use ($batch, $reason) {
            $this->releaseBatchReservation($batch, $reason);

            return $this->transition($batch, DistributionBatchStatus::Cancelled, 'distribution_batch_cancelled', [], ['reason' => $reason]);
        });
    }

    // ----------------------------------------------------------------- helpers

    private function createBatchDistribution(DistributionBatch $batch, DistributionBeneficiary $beneficiary): Distribution
    {
        $distribution = new Distribution;
        $distribution->fill([
            'distribution_type' => $batch->distribution_type->value,
            'source_type' => $batch->program_id !== null ? DistributionSourceType::Program->value : DistributionSourceType::Direct->value,
            'program_id' => $batch->program_id,
            'mustahik_id' => $beneficiary->mustahik_id,
            'fund_id' => $batch->fund_id,
            'requested_amount' => $beneficiary->approved_amount,
            'description' => 'Batch '.$batch->batch_number.' — '.$batch->name,
        ]);
        $distribution->distribution_number = app(BusinessNumberService::class)->next('DST');
        $distribution->currency = $batch->fund->currency;
        // Approval batch berlaku sebagai approval tiap distribution di dalamnya.
        $distribution->approved_amount = $beneficiary->approved_amount;
        $distribution->distributed_amount = 0;
        $distribution->status = DistributionStatus::Processing;
        $distribution->batch_id = $batch->id;
        $distribution->created_by = $batch->created_by;
        $distribution->approved_by = $batch->approved_by;
        $distribution->approved_at = $batch->approved_at;
        $distribution->save();

        if ($batch->program_id !== null) {
            $program = Program::findOrFail($batch->program_id);
            $budget = $program->budgets()->whereIn('status', ['draft', 'active'])->first()
                ?? throw ZakatException::conflict('Program belum memiliki budget aktif.');

            $this->programs->commit($program, [
                'program_budget_id' => $budget->id,
                'distribution_id' => $distribution->id,
                'amount' => $beneficiary->approved_amount,
                'currency' => $distribution->currency,
                'reason' => $batch->batch_number,
            ]);
        }

        $this->audit->record('distribution_created', $distribution, context: ['batch_id' => $batch->id]);

        return $distribution;
    }

    private function releaseBatchReservation(DistributionBatch $batch, string $reason): void
    {
        $reservation = FundReservation::where('target_type', 'distribution_batch')
            ->where('target_id', $batch->id)
            ->where('status', 'active')
            ->first();

        if ($reservation !== null) {
            $this->funds->releaseReservation($reservation, $reason);
        }
    }

    private function recalculate(DistributionBatch $batch): void
    {
        $beneficiaries = DistributionBeneficiary::where('batch_id', $batch->id);

        $batch->forceFill([
            'total_amount' => (string) $beneficiaries->sum('approved_amount'),
            'total_beneficiary' => $beneficiaries->count(),
        ])->saveQuietly();
    }

    private function assertEditable(DistributionBatch $batch): void
    {
        if (! in_array($batch->status, [DistributionBatchStatus::Draft, DistributionBatchStatus::Validated], true)) {
            throw ZakatException::invalidTransition('Beneficiary hanya dapat diubah selama batch masih draft.');
        }
    }

    private function assertTransition(DistributionBatch $batch, DistributionBatchStatus $next): void
    {
        if (! $batch->status->canTransitionTo($next)) {
            throw ZakatException::invalidTransition("Batch berstatus {$batch->status->value} tidak dapat berpindah ke {$next->value}.");
        }
    }

    private function transition(DistributionBatch $batch, DistributionBatchStatus $status, string $event, array $attributes = [], array $context = []): DistributionBatch
    {
        $previous = $batch->status;

        $batch->forceFill($attributes + ['status' => $status])->saveQuietly();

        $this->audit->record($event, $batch, ['status' => $previous->value], ['status' => $status->value], $context);

        return $batch;
    }
}
