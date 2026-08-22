<?php

namespace App\Services;

use App\Models\CollectionPayment;
use App\Models\FundMovement;
use App\Models\Payment;
use App\Models\ReconciliationTransaction;
use App\Support\OrganizationContext;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * PRD 14H — sisi internal pencocokan.
 *
 * Tanpa ini `reconciliation_transactions` selalu kosong dan auto matching tidak
 * pernah menemukan pasangan, sehingga seluruh modul rekonsiliasi tidak berguna.
 *
 * Modul ini hanya menyalin ringkasan transaksi milik modul lain. Kebenaran
 * datanya tetap milik modul asal; di sini hanya bayangannya untuk dicocokkan.
 */
class ReconciliationSyncService
{
    public function __construct(private readonly AuditService $audit) {}

    /**
     * Tarik transaksi internal pada rentang tanggal tertentu.
     *
     * Idempoten: dijaga unique index (organization_id, source_type, source_id),
     * jadi menjalankan ulang tidak menggandakan baris.
     *
     * @return array<string, int>
     */
    public function sync(string $from, string $to): array
    {
        $organizationId = OrganizationContext::requireId();

        $summary = [
            'PAYMENT' => $this->syncPayments($organizationId, $from, $to),
            'COLLECTION' => $this->syncCollectionPayments($organizationId, $from, $to),
            'DISTRIBUTION' => $this->syncDistributionOutflows($organizationId, $from, $to),
        ];

        $this->audit->record('reconciliation_internal_synced', null, context: $summary + ['from' => $from, 'to' => $to]);

        return $summary;
    }

    /** PRD 14H §23 — pencatatan manual untuk transaksi yang tidak berasal dari modul lain. */
    public function createManual(array $data): ReconciliationTransaction
    {
        $transaction = new ReconciliationTransaction;
        $transaction->fill($data + ['status' => 'UNMATCHED']);
        $transaction->organization_id = OrganizationContext::requireId();
        $transaction->source_type = 'MANUAL';
        $transaction->source_id = null;
        $transaction->currency = $data['currency'] ?? 'IDR';
        $transaction->save();

        $this->audit->record('reconciliation_internal_created', $transaction);

        return $transaction;
    }

    private function syncPayments(string $organizationId, string $from, string $to): int
    {
        $rows = Payment::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->get(['id', 'payment_number', 'paid_at', 'amount', 'currency']);

        return $this->store($organizationId, 'PAYMENT', $rows->map(fn ($row) => [
            'source_id' => $row->id,
            'transaction_reference' => $row->payment_number,
            'transaction_date' => $row->paid_at?->toDateString(),
            'amount' => (string) $row->amount,
            'currency' => $row->currency,
            'direction' => 'INFLOW',
        ])->all());
    }

    private function syncCollectionPayments(string $organizationId, string $from, string $to): int
    {
        // CollectionPayment tidak ber-scope organisasi sendiri; dibatasi lewat
        // collection induknya.
        $rows = CollectionPayment::query()
            ->whereHas('collection')
            ->where('status', 'settled')
            ->whereBetween('payment_date', [$from.' 00:00:00', $to.' 23:59:59'])
            ->get(['id', 'payment_reference', 'payment_date', 'amount', 'currency']);

        return $this->store($organizationId, 'COLLECTION', $rows->map(fn ($row) => [
            'source_id' => $row->id,
            'transaction_reference' => $row->payment_reference,
            'transaction_date' => $row->payment_date?->toDateString(),
            'amount' => (string) $row->amount,
            'currency' => $row->currency,
            'direction' => 'INFLOW',
        ])->all());
    }

    private function syncDistributionOutflows(string $organizationId, string $from, string $to): int
    {
        $rows = FundMovement::query()
            ->where('movement_type', 'distribution')
            ->where('direction', 'out')
            ->whereBetween('effective_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->get(['id', 'movement_number', 'effective_at', 'amount', 'currency']);

        return $this->store($organizationId, 'DISTRIBUTION', $rows->map(fn ($row) => [
            'source_id' => $row->id,
            'transaction_reference' => $row->movement_number,
            'transaction_date' => $row->effective_at?->toDateString(),
            'amount' => (string) $row->amount,
            'currency' => $row->currency,
            'direction' => 'OUTFLOW',
        ])->all());
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function store(string $organizationId, string $sourceType, array $rows): int
    {
        $created = 0;

        foreach ($rows as $row) {
            if ($row['transaction_date'] === null) {
                continue;
            }

            $exists = ReconciliationTransaction::query()
                ->where('source_type', $sourceType)
                ->where('source_id', $row['source_id'])
                ->exists();

            if ($exists) {
                continue;
            }

            // Savepoint: dua sinkronisasi bersamaan bisa menabrak unique index,
            // dan pada PostgreSQL kegagalan statement membatalkan seluruh
            // transaksi induk bila tidak dibatasi.
            try {
                DB::transaction(function () use ($organizationId, $sourceType, $row) {
                    $transaction = new ReconciliationTransaction;
                    $transaction->fill([
                        'transaction_reference' => $row['transaction_reference'],
                        'transaction_date' => $row['transaction_date'],
                        'amount' => $row['amount'],
                        'currency' => $row['currency'] ?? 'IDR',
                        'direction' => $row['direction'],
                        'status' => 'UNMATCHED',
                    ]);
                    $transaction->organization_id = $organizationId;
                    $transaction->source_type = $sourceType;
                    $transaction->source_id = $row['source_id'];
                    $transaction->save();
                });

                $created++;
            } catch (UniqueConstraintViolationException) {
                // Sudah ada, tidak perlu diperlakukan sebagai kegagalan.
            }
        }

        return $created;
    }
}
