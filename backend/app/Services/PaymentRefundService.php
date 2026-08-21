<?php

namespace App\Services;

use App\Enums\PaymentRefundStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\ZakatException;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Payments\PaymentDriverManager;
use Illuminate\Support\Facades\DB;

/** PRD 13O — struktur refund. Implementasi awal berupa permintaan manual. */
class PaymentRefundService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly PaymentDriverManager $drivers,
        private readonly PaymentProviderService $providers,
    ) {}

    public function find(string $id): PaymentRefund
    {
        return PaymentRefund::query()->with('payment')->find($id)
            ?? throw ZakatException::notFound('Refund tidak ditemukan.');
    }

    /** @param array<string, mixed> $data */
    public function request(Payment $payment, array $data): PaymentRefund
    {
        // PRD 13O §29 — hanya payment yang sudah lunas yang dapat direfund.
        if ($payment->status !== PaymentStatus::Paid) {
            throw ZakatException::conflict('Hanya payment berstatus paid yang dapat direfund.');
        }

        $amount = (string) $data['amount'];

        if (bccomp($amount, '0', 2) <= 0) {
            throw ZakatException::conflict('Nominal refund harus lebih besar dari nol.');
        }

        if (bccomp($amount, $payment->refundableAmount(), 2) > 0) {
            throw ZakatException::conflict('Nominal refund melebihi sisa yang dapat dikembalikan.');
        }

        $refund = new PaymentRefund;
        $refund->fill(['reason' => $data['reason']]);
        $refund->organization_id = $payment->organization_id;
        $refund->payment_id = $payment->id;
        $refund->amount = $amount;
        $refund->status = PaymentRefundStatus::Requested;
        $refund->requested_by = auth()->id();
        $refund->requested_at = now();
        $refund->save();

        $this->audit->record('payment_refund_requested', $refund, context: ['amount' => $amount]);

        return $refund;
    }

    public function approve(PaymentRefund $refund): PaymentRefund
    {
        $this->assertPending($refund);

        // Maker checker: pemohon tidak boleh menyetujui permintaannya sendiri.
        if ($refund->requested_by !== null && $refund->requested_by === auth()->id()) {
            throw ZakatException::forbidden('Pemohon tidak dapat menyetujui refund sendiri.');
        }

        return DB::transaction(function () use ($refund) {
            $payment = $refund->payment;
            $provider = $this->providers->find($payment->provider_id);
            $result = $this->drivers->for($provider)->refund($provider, $payment, (string) $refund->amount, (string) $refund->reason);

            $refund->forceFill([
                'status' => $result['accepted'] ? PaymentRefundStatus::Completed : PaymentRefundStatus::Failed,
                'approved_by' => auth()->id(),
                'processed_at' => now(),
            ])->saveQuietly();

            $this->audit->record('payment_refund_approved', $refund, context: ['message' => $result['message'] ?? null]);

            // Payment menjadi refunded hanya bila seluruh nominalnya dikembalikan.
            if ($result['accepted'] && bccomp($payment->refundableAmount(), '0', 2) === 0) {
                $payment->forceFill(['status' => PaymentStatus::Refunded])->saveQuietly();
                $this->audit->record('payment_refunded', $payment);
            }

            if ($result['accepted']) {
                $this->audit->record('payment_refund_completed', $refund);
            }

            return $refund;
        });
    }

    public function reject(PaymentRefund $refund, string $reason): PaymentRefund
    {
        $this->assertPending($refund);

        $refund->forceFill([
            'status' => PaymentRefundStatus::Rejected,
            'approved_by' => auth()->id(),
            'processed_at' => now(),
            'rejection_reason' => $reason,
        ])->saveQuietly();

        $this->audit->record('payment_refund_rejected', $refund, context: ['reason' => $reason]);

        return $refund;
    }

    private function assertPending(PaymentRefund $refund): void
    {
        if ($refund->status !== PaymentRefundStatus::Requested) {
            throw ZakatException::invalidTransition('Refund tidak lagi menunggu keputusan.');
        }
    }
}
