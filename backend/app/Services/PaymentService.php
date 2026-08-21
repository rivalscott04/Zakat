<?php

namespace App\Services;

use App\Enums\PaymentFailureReason;
use App\Enums\PaymentReconciliationResult;
use App\Enums\PaymentSourceType;
use App\Enums\PaymentStatus;
use App\Exceptions\ZakatException;
use App\Models\Payment;
use App\Models\PaymentReconciliation;
use App\Payments\PaymentDriverManager;
use App\Payments\PaymentSourceResolver;
use App\Support\OrganizationContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/** PRD 13C sampai 13N — siklus hidup payment. */
class PaymentService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly PaymentDriverManager $drivers,
        private readonly PaymentSourceResolver $sources,
        private readonly PaymentProviderService $providers,
    ) {}

    /** @param array<string, mixed> $filters */
    public function list(array $filters): LengthAwarePaginator
    {
        return Payment::query()
            ->with('provider:id,provider_code,name')
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['source_type'] ?? null, fn ($q, $v) => $q->where('source_type', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where(
                fn ($x) => $x->where('payment_number', 'ilike', "%{$v}%")->orWhere('provider_reference', 'ilike', "%{$v}%")
            ))
            ->latest()
            ->paginate(min((int) ($filters['per_page'] ?? 15), (int) config('zakat.pagination.max_per_page')));
    }

    public function find(string $id): Payment
    {
        return Payment::query()
            ->with(['provider:id,provider_code,name', 'webhooks', 'refunds', 'reconciliations'])
            ->find($id) ?? throw ZakatException::notFound('Payment tidak ditemukan.');
    }

    /** PRD 13G §14 dan §15. */
    public function create(array $data): Payment
    {
        $provider = $this->providers->find($data['provider_id']);

        if (! $provider->isActive()) {
            throw ZakatException::conflict('Payment provider tidak aktif.');
        }

        $sourceType = PaymentSourceType::from($data['source_type']);
        $amount = (string) $data['amount'];

        if (bccomp($amount, '0', 2) <= 0) {
            throw ZakatException::conflict('Nominal payment harus lebih besar dari nol.');
        }

        $collection = $this->sources->assertPayable($sourceType, $data['source_id'], $amount);

        return DB::transaction(function () use ($data, $provider, $amount, $collection) {
            $payment = new Payment;
            $payment->fill(collect($data)->only(['source_type', 'source_id', 'payer_name', 'payer_email', 'payer_phone', 'payment_method', 'internal_reference', 'metadata'])->all());
            $payment->organization_id = OrganizationContext::requireId();
            $payment->provider_id = $provider->id;
            // Nominal dan mata uang diambil dari transaksi sumber, bukan dari
            // payload client (PRD 13V §44 soal manipulasi amount).
            $payment->amount = $amount;
            $payment->currency = $collection->currency;
            $payment->status = PaymentStatus::Created;
            $payment->created_by = auth()->id();
            $payment->save();

            $response = $this->drivers->for($provider)->createPayment($provider, $payment);

            $payment->forceFill([
                'provider_reference' => $response['provider_reference'] ?? null,
                'payment_url' => $response['payment_url'] ?? null,
                'payment_method' => $response['payment_method'] ?? $payment->payment_method?->value,
                'expires_at' => $response['expires_at'] ?? null,
                'status' => PaymentStatus::Pending,
            ])->saveQuietly();

            $this->audit->record('payment_created', $payment);
            $this->audit->record('payment_pending', $payment);

            return $this->find($payment->id);
        });
    }

    /**
     * PRD 13K §21 — satu-satunya jalan sebuah payment menjadi lunas.
     *
     * Dipakai baik oleh webhook maupun verifikasi manual, supaya efek sampingnya
     * ke modul sumber selalu sama.
     */
    public function markPaid(Payment $payment, array $context = []): Payment
    {
        $this->assertTransition($payment, PaymentStatus::Paid);

        return DB::transaction(function () use ($payment, $context) {
            $payment->forceFill([
                'status' => PaymentStatus::Paid,
                'paid_at' => $context['paid_at'] ?? now(),
            ])->saveQuietly();

            $this->audit->record('payment_paid', $payment, context: $context);

            // PRD 13U §14 dan §16 — modul sumber yang menjalankan bisnisnya.
            $this->sources->onPaid($payment);

            return $payment;
        });
    }

    /** PRD 13K §22 — verifikasi manual wajib beralasan dan tercatat. */
    public function verifyManually(Payment $payment, string $reason): Payment
    {
        $payment->forceFill([
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'verification_reason' => $reason,
        ])->saveQuietly();

        $payment = $this->markPaid($payment, ['verification' => 'manual']);

        $this->audit->record('payment_manually_verified', $payment, context: ['reason' => $reason]);

        return $payment;
    }

    public function cancel(Payment $payment, string $reason): Payment
    {
        $this->assertTransition($payment, PaymentStatus::Cancelled);

        $payment->forceFill([
            'status' => PaymentStatus::Cancelled,
            'cancelled_by' => auth()->id(),
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ])->saveQuietly();

        $this->audit->record('payment_cancelled', $payment, context: ['reason' => $reason]);

        return $payment;
    }

    public function fail(Payment $payment, PaymentFailureReason $reason, ?string $note = null): Payment
    {
        $this->assertTransition($payment, PaymentStatus::Failed);

        $payment->forceFill([
            'status' => PaymentStatus::Failed,
            'failure_reason' => $reason->value,
            'failure_note' => $note,
        ])->saveQuietly();

        $this->audit->record('payment_failed', $payment, context: ['failure_reason' => $reason->value]);

        return $payment;
    }

    /** PRD 13M §25 — payment kedaluwarsa tidak dapat dipakai lagi. */
    public function expire(Payment $payment): Payment
    {
        $this->assertTransition($payment, PaymentStatus::Expired);

        $payment->forceFill(['status' => PaymentStatus::Expired])->saveQuietly();
        $this->audit->record('payment_expired', $payment);

        return $payment;
    }

    /** PRD 13Q §33 — tanyakan status terkini ke provider. */
    public function refreshStatus(Payment $payment): Payment
    {
        $provider = $this->providers->find($payment->provider_id);
        $result = $this->drivers->for($provider)->getPaymentStatus($provider, $payment);

        if ($payment->isExpired()) {
            return $this->expire($payment);
        }

        $reported = $result['status'] ?? null;

        if ($reported === PaymentStatus::Paid->value && $payment->status !== PaymentStatus::Paid) {
            return $this->markPaid($payment, ['source' => 'refresh_status']);
        }

        return $payment;
    }

    /** PRD 13P §30 — bandingkan catatan internal dengan jawaban provider. */
    public function reconcile(Payment $payment): PaymentReconciliation
    {
        $provider = $this->providers->find($payment->provider_id);
        $reported = $this->drivers->for($provider)->getPaymentStatus($provider, $payment);

        $providerAmount = $reported['amount'] ?? null;
        $providerStatus = $reported['status'] ?? null;

        $amountMatches = $providerAmount !== null && bccomp((string) $payment->amount, $providerAmount, 2) === 0;
        $statusMatches = $providerStatus !== null && $providerStatus === $payment->status->value;

        $result = match (true) {
            $providerAmount === null || $providerStatus === null => PaymentReconciliationResult::PendingReview,
            $amountMatches && $statusMatches => PaymentReconciliationResult::Matched,
            default => PaymentReconciliationResult::Mismatched,
        };

        $reconciliation = PaymentReconciliation::create([
            'organization_id' => $payment->organization_id,
            'payment_id' => $payment->id,
            'provider_reference' => $payment->provider_reference,
            'internal_amount' => $payment->amount,
            'provider_amount' => $providerAmount,
            'internal_status' => $payment->status->value,
            'provider_status' => $providerStatus,
            'result' => $result,
            'reconciled_at' => now(),
            'reconciled_by' => auth()->id(),
        ]);

        $this->audit->record(
            $result === PaymentReconciliationResult::Mismatched ? 'payment_reconciliation_mismatched' : 'payment_reconciliation_created',
            $payment,
            context: ['result' => $result->value],
        );

        return $reconciliation;
    }

    /** @return array<string, mixed> */
    public function summary(): array
    {
        $rows = Payment::query()
            ->selectRaw('status, count(*) as total, coalesce(sum(amount), 0)::text as amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return collect(PaymentStatus::cases())->mapWithKeys(fn ($status) => [
            $status->value => [
                'total' => (int) ($rows[$status->value]->total ?? 0),
                'amount' => (string) ($rows[$status->value]->amount ?? '0.00'),
            ],
        ])->all();
    }

    private function assertTransition(Payment $payment, PaymentStatus $next): void
    {
        if (! $payment->status->canTransitionTo($next)) {
            throw ZakatException::invalidTransition("Payment berstatus {$payment->status->value} tidak dapat berpindah ke {$next->value}.");
        }
    }
}
