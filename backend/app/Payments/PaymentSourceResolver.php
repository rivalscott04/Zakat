<?php

namespace App\Payments;

use App\Enums\PaymentSourceType;
use App\Exceptions\ZakatException;
use App\Models\Collection;
use App\Models\Payment;
use App\Services\CollectionService;

/**
 * PRD 13D §10 dan PRD 13L §24.
 *
 * Payment Gateway tidak boleh mengetahui struktur internal modul sumber, dan
 * tidak boleh membuat transaksi bisnis sendiri (PRD 13U §2 dan §16). Kelas ini
 * satu-satunya tempat yang menerjemahkan `source_type` menjadi aksi pada modul
 * pemilik transaksi.
 *
 * Seluruh source type yang ada saat ini bermuara pada modul Collection: yang
 * membedakan zakat, infak, dan sedekah adalah jenis zakat pada collection itu
 * sendiri, bukan tabel yang berbeda.
 */
class PaymentSourceResolver
{
    public function __construct(private readonly CollectionService $collections) {}

    /** PRD 13G §15 — transaksi sumber harus sah sebelum payment dibuat. */
    public function assertPayable(PaymentSourceType $sourceType, string $sourceId, string $amount): Collection
    {
        $collection = Collection::find($sourceId)
            ?? throw ZakatException::notFound('Transaksi sumber tidak ditemukan.');

        if (! in_array($collection->status->value, ['pending', 'partially_paid'], true)) {
            throw ZakatException::conflict("Collection berstatus {$collection->status->value} tidak menerima pembayaran baru.");
        }

        if (bccomp($amount, (string) $collection->remaining_amount, 2) > 0) {
            throw ZakatException::conflict('Nominal payment melebihi sisa tagihan collection.');
        }

        return $collection;
    }

    /**
     * PRD 13L §24 — modul sumber yang menjalankan bisnisnya, bukan modul ini.
     *
     * Pencatatan penerimaan, pemutakhiran collection, dan lanjutannya ke Fund
     * serta Accounting seluruhnya ditangani CollectionService.
     */
    public function onPaid(Payment $payment): void
    {
        $collection = Collection::find($payment->source_id);

        if ($collection === null) {
            return;
        }

        $collectionPayment = $this->collections->payment($collection, [
            'payment_reference' => $payment->payment_number,
            'payment_method' => $payment->payment_method?->value ?? 'manual',
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'payment_date' => $payment->paid_at ?? now(),
            'metadata' => [
                'payment_id' => $payment->id,
                'provider_reference' => $payment->provider_reference,
            ],
        ]);

        $this->collections->verifyPayment($collectionPayment, 'settled');
    }
}
