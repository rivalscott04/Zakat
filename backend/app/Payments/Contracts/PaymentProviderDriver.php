<?php

namespace App\Payments\Contracts;

use App\Models\Payment;
use App\Models\PaymentProvider;

/**
 * PRD 13B §6 — kontrak adapter provider pembayaran.
 *
 * Menambah provider baru cukup dengan menulis satu kelas yang memenuhi kontrak
 * ini lalu mendaftarkannya di PaymentDriverManager. Tidak ada bagian lain dari
 * modul yang perlu diubah.
 */
interface PaymentProviderDriver
{
    /**
     * Buat transaksi di sisi provider.
     *
     * @return array{provider_reference: string, payment_url: ?string, payment_method: ?string, expires_at: ?\DateTimeInterface}
     */
    public function createPayment(PaymentProvider $provider, Payment $payment): array;

    /**
     * Tanyakan status terkini ke provider.
     *
     * @return array{status: ?string, amount: ?string, raw: array<string, mixed>}
     */
    public function getPaymentStatus(PaymentProvider $provider, Payment $payment): array;

    /**
     * PRD 13H §17 — verifikasi keaslian webhook.
     *
     * Wajib gagal tertutup: bila rahasia belum dikonfigurasi atau tanda tangan
     * tidak cocok, kembalikan false.
     *
     * @param  array<string, string>  $headers
     */
    public function verifyWebhook(PaymentProvider $provider, string $rawBody, array $headers): bool;

    /**
     * Terjemahkan payload webhook menjadi bentuk yang dipahami modul ini.
     *
     * @param  array<string, mixed>  $payload
     * @return array{event_id: ?string, event_type: ?string, provider_reference: ?string, status: ?string, amount: ?string}
     */
    public function parseWebhook(PaymentProvider $provider, array $payload): array;

    /**
     * PRD 13O §29 — pengembalian dana di sisi provider.
     *
     * @return array{accepted: bool, provider_reference: ?string, message: ?string}
     */
    public function refund(PaymentProvider $provider, Payment $payment, string $amount, string $reason): array;
}
