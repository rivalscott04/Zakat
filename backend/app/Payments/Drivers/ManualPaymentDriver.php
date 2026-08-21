<?php

namespace App\Payments\Drivers;

use App\Models\Payment;
use App\Models\PaymentProvider;
use App\Payments\Contracts\PaymentProviderDriver;
use Illuminate\Support\Str;

/**
 * PRD 13B §6 — driver awal.
 *
 * Tidak memanggil layanan luar. Payment dianggap dibayar di luar sistem lalu
 * dikonfirmasi lewat verifikasi manual atau webhook bertanda tangan. Driver ini
 * yang dipakai sampai penyedia pembayaran sungguhan dipilih.
 */
class ManualPaymentDriver implements PaymentProviderDriver
{
    public const CODE = 'manual';

    public function createPayment(PaymentProvider $provider, Payment $payment): array
    {
        $config = $provider->config_encrypted ?? [];

        return [
            // Referensi dibuat lokal supaya alur rekonsiliasi tetap dapat diuji.
            'provider_reference' => 'MAN-'.strtoupper((string) Str::ulid()),
            'payment_url' => null,
            'payment_method' => $payment->payment_method?->value ?? 'manual',
            'expires_at' => now()->addHours((int) ($config['expires_hours'] ?? 24)),
        ];
    }

    public function getPaymentStatus(PaymentProvider $provider, Payment $payment): array
    {
        // Tanpa layanan luar, status yang tercatat di sistem adalah satu-satunya
        // yang diketahui. Perubahan datang lewat webhook atau verifikasi manual.
        return [
            'status' => $payment->status->value,
            'amount' => (string) $payment->amount,
            'raw' => ['driver' => self::CODE, 'note' => 'Driver manual tidak menghubungi layanan eksternal.'],
        ];
    }

    public function verifyWebhook(PaymentProvider $provider, string $rawBody, array $headers): bool
    {
        $secret = $provider->webhook_secret_encrypted;

        // Gagal tertutup: tanpa rahasia, tidak ada yang bisa dipercaya.
        if (blank($secret)) {
            return false;
        }

        $signature = $headers['x-signature'] ?? $headers['X-Signature'] ?? '';

        if (! is_string($signature) || $signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);
    }

    public function parseWebhook(PaymentProvider $provider, array $payload): array
    {
        return [
            'event_id' => isset($payload['event_id']) ? (string) $payload['event_id'] : null,
            'event_type' => isset($payload['event_type']) ? (string) $payload['event_type'] : null,
            'provider_reference' => isset($payload['provider_reference']) ? (string) $payload['provider_reference'] : null,
            'status' => isset($payload['status']) ? (string) $payload['status'] : null,
            'amount' => isset($payload['amount']) ? (string) $payload['amount'] : null,
        ];
    }

    public function refund(PaymentProvider $provider, Payment $payment, string $amount, string $reason): array
    {
        // PRD 13O §27 — implementasi awal hanya mencatat permintaan; dananya
        // dikembalikan di luar sistem lalu ditandai selesai oleh petugas.
        return [
            'accepted' => true,
            'provider_reference' => null,
            'message' => 'Refund dicatat untuk diproses manual di luar sistem.',
        ];
    }
}
