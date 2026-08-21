<?php

namespace App\Services;

use App\Enums\PaymentFailureReason;
use App\Enums\PaymentStatus;
use App\Enums\PaymentWebhookStatus;
use App\Models\Payment;
use App\Models\PaymentProvider;
use App\Models\PaymentWebhook;
use App\Payments\PaymentDriverManager;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * PRD 13H, 13I, dan 13J — penerimaan notifikasi dari provider.
 *
 * Endpoint ini tidak terautentikasi, jadi seluruh kepercayaan bertumpu pada
 * tanda tangan. Setiap penerimaan dicatat, termasuk yang ditolak, supaya upaya
 * pemalsuan meninggalkan jejak (PRD 13U §9).
 */
class PaymentWebhookService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly PaymentDriverManager $drivers,
        private readonly PaymentService $payments,
    ) {}

    /**
     * @return array{status: string, message: string}
     */
    public function handle(PaymentProvider $provider, Request $request): array
    {
        $rawBody = $request->getContent();
        $payload = json_decode($rawBody, true);
        $payload = is_array($payload) ? $payload : [];

        $driver = $this->drivers->for($provider);
        $signatureValid = $driver->verifyWebhook($provider, $rawBody, $this->headers($request));
        $parsed = $driver->parseWebhook($provider, $payload);

        // Dicatat lebih dulu, apa pun hasilnya.
        $record = $this->record($provider, $request, $parsed, $payload, $signatureValid);

        if ($record === null) {
            // Event dengan id yang sama sudah pernah masuk (PRD 13J §20).
            return ['status' => 'duplicate', 'message' => 'Event sudah pernah diproses.'];
        }

        $this->audit->record('payment_webhook_received', $record, organizationId: $provider->organization_id);

        if (! $signatureValid) {
            $this->finish($record, PaymentWebhookStatus::Failed, 'Tanda tangan webhook tidak valid.');
            $this->audit->record('payment_webhook_failed', $record, context: ['reason' => 'signature_invalid'], organizationId: $provider->organization_id);

            return ['status' => 'rejected', 'message' => 'Tanda tangan tidak valid.'];
        }

        $this->audit->record('payment_webhook_verified', $record, organizationId: $provider->organization_id);

        $payment = $this->matchPayment($provider, $parsed);

        if ($payment === null) {
            $this->finish($record, PaymentWebhookStatus::Ignored, 'Payment tidak ditemukan untuk referensi provider.');

            return ['status' => 'ignored', 'message' => 'Payment tidak dikenal.'];
        }

        $record->forceFill(['payment_id' => $payment->id])->saveQuietly();

        return $this->apply($record, $payment, $parsed);
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array{status: string, message: string}
     */
    private function apply(PaymentWebhook $record, Payment $payment, array $parsed): array
    {
        $reported = $parsed['status'] ?? null;

        // PRD 13K §21 — nominal wajib cocok sebelum status diubah.
        if ($reported === PaymentStatus::Paid->value) {
            $amount = $parsed['amount'] ?? null;

            if ($amount === null || bccomp((string) $payment->amount, (string) $amount, 2) !== 0) {
                $this->finish($record, PaymentWebhookStatus::Failed, 'Nominal pada webhook tidak sama dengan nominal payment.');
                $this->payments->fail($payment, PaymentFailureReason::InvalidAmount, 'Nominal webhook tidak cocok.');

                return ['status' => 'rejected', 'message' => 'Nominal tidak cocok.'];
            }

            if ($payment->status === PaymentStatus::Paid) {
                $this->finish($record, PaymentWebhookStatus::Ignored, 'Payment sudah lunas.');

                return ['status' => 'ok', 'message' => 'Payment sudah lunas.'];
            }

            $this->payments->markPaid($payment, ['source' => 'webhook', 'webhook_id' => $record->id]);
            $this->finish($record, PaymentWebhookStatus::Processed);

            return ['status' => 'ok', 'message' => 'Payment ditandai lunas.'];
        }

        if (in_array($reported, [PaymentStatus::Failed->value, PaymentStatus::Expired->value, PaymentStatus::Cancelled->value], true)
            && $payment->status->canTransitionTo(PaymentStatus::from($reported))) {
            match ($reported) {
                PaymentStatus::Failed->value => $this->payments->fail($payment, PaymentFailureReason::ProviderError, 'Dilaporkan gagal oleh provider.'),
                PaymentStatus::Expired->value => $this->payments->expire($payment),
                default => $this->payments->cancel($payment, 'Dibatalkan oleh provider.'),
            };

            $this->finish($record, PaymentWebhookStatus::Processed);

            return ['status' => 'ok', 'message' => "Payment ditandai {$reported}."];
        }

        $this->finish($record, PaymentWebhookStatus::Ignored, 'Status tidak memerlukan tindakan.');

        return ['status' => 'ok', 'message' => 'Tidak ada perubahan.'];
    }

    /**
     * Referensi provider dicari khusus di dalam organisasi pemilik provider,
     * karena pada endpoint webhook tidak ada organization context aktif.
     *
     * @param  array<string, mixed>  $parsed
     */
    private function matchPayment(PaymentProvider $provider, array $parsed): ?Payment
    {
        $reference = $parsed['provider_reference'] ?? null;

        if ($reference === null) {
            return null;
        }

        return Payment::query()
            ->acrossOrganizations()
            ->where('organization_id', $provider->organization_id)
            ->where('provider_id', $provider->id)
            ->where('provider_reference', $reference)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @param  array<string, mixed>  $payload
     */
    private function record(PaymentProvider $provider, Request $request, array $parsed, array $payload, bool $signatureValid): ?PaymentWebhook
    {
        $eventId = $parsed['event_id'] ?? null;

        // PRD 13J §20 — jalur cepat: event yang sudah tercatat langsung ditolak.
        if ($eventId !== null && PaymentWebhook::where('provider_id', $provider->id)->where('event_id', $eventId)->exists()) {
            return null;
        }

        $attributes = [
            'organization_id' => $provider->organization_id,
            'provider_id' => $provider->id,
            'event_id' => $eventId,
            'event_type' => $parsed['event_type'] ?? null,
            'signature_valid' => $signatureValid,
            // Payload disimpan setelah disaring supaya rahasia tidak ikut
            // mengendap di database (PRD 01 §41).
            'payload' => app(AuditService::class)->redact($payload),
            'ip_address' => $request->ip(),
            'received_at' => now(),
            'status' => PaymentWebhookStatus::Received,
        ];

        try {
            // Dibungkus transaksi tersendiri supaya pelanggaran unique dari dua
            // kiriman yang tiba bersamaan hanya membatalkan sampai savepoint.
            // Tanpa ini, PostgreSQL membatalkan seluruh transaksi induk dan
            // setiap query sesudahnya ikut gagal.
            return DB::transaction(fn () => PaymentWebhook::create($attributes));
        } catch (UniqueConstraintViolationException) {
            return null;
        }
    }

    private function finish(PaymentWebhook $record, PaymentWebhookStatus $status, ?string $message = null): void
    {
        $record->forceFill([
            'status' => $status,
            'processed_at' => now(),
            'error_message' => $message,
        ])->saveQuietly();

        if ($status === PaymentWebhookStatus::Processed) {
            $this->audit->record('payment_webhook_processed', $record, organizationId: $record->organization_id);
        }
    }

    /** @return array<string, string> */
    private function headers(Request $request): array
    {
        return collect($request->headers->all())
            ->map(fn ($values) => is_array($values) ? (string) ($values[0] ?? '') : (string) $values)
            ->all();
    }
}
