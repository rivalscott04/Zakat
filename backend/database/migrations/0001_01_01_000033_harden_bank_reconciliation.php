<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menyelaraskan modul 14 dengan Core PRD §12 dan menutup celah idempotency.
 */
return new class extends Migration
{
    /** @var array<string, array<int, string>> */
    private const MONEY = [
        'bank_accounts' => ['opening_balance', 'current_balance'],
        'bank_statements' => ['opening_balance', 'closing_balance'],
        'bank_transactions' => ['debit_amount', 'credit_amount', 'balance'],
        'reconciliation_transactions' => ['amount'],
        'reconciliation_matches' => ['matched_amount'],
        'reconciliation_sessions' => ['opening_balance', 'closing_balance', 'matched_amount', 'unmatched_amount', 'difference_amount'],
        'reconciliation_adjustments' => ['amount'],
    ];

    public function up(): void
    {
        // Core PRD §12 mewajibkan NUMERIC(20,2); modul ini memakai 18,2 sehingga
        // berbeda sendiri dari fund, accounting, distribution, dan payment.
        $this->scale(20);

        // Satu transaksi internal hanya boleh terwakili satu baris, supaya
        // sinkronisasi berulang tidak menggandakan sisi internal rekonsiliasi.
        DB::statement(
            'CREATE UNIQUE INDEX reconciliation_transactions_source_unique
             ON reconciliation_transactions (organization_id, source_type, source_id)
             WHERE source_id IS NOT NULL'
        );

        // Satu transaksi bank tidak boleh dicocokkan berkali-kali ke transaksi
        // internal yang sama.
        DB::statement(
            'CREATE UNIQUE INDEX reconciliation_matches_pair_unique
             ON reconciliation_matches (bank_transaction_id, reconciliation_transaction_id)'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS reconciliation_matches_pair_unique');
        DB::statement('DROP INDEX IF EXISTS reconciliation_transactions_source_unique');
        $this->scale(18);
    }

    private function scale(int $precision): void
    {
        foreach (self::MONEY as $table => $columns) {
            foreach ($columns as $column) {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE NUMERIC({$precision},2)");
            }
        }
    }
};
