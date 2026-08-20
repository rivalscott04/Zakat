<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * F-07 — kolom uang collection disamakan dengan Core PRD §12: NUMERIC(20,2).
 *
 * Sebelumnya collection memakai 20,8 sedangkan fund, accounting, program, dan
 * distribution memakai 20,2, sehingga dana yang mengalir dari collection ke fund
 * dibulatkan tanpa jejak.
 *
 * `collection_items.quantity` sengaja dibiarkan 20,8 karena itu kuantitas
 * (misalnya berat emas), bukan nilai uang.
 *
 * Catatan: nilai lama dengan lebih dari dua desimal akan dibulatkan oleh
 * PostgreSQL dan tidak dapat dipulihkan oleh `down()`.
 */
return new class extends Migration
{
    private const MONEY_COLUMNS = [
        'collections' => ['expected_amount', 'paid_amount', 'remaining_amount'],
        'collection_items' => ['expected_amount', 'paid_amount', 'remaining_amount'],
        'collection_payments' => ['amount'],
        'payment_allocations' => ['allocated_amount'],
    ];

    public function up(): void
    {
        $this->changeScale(2);
    }

    public function down(): void
    {
        $this->changeScale(8);
    }

    private function changeScale(int $scale): void
    {
        foreach (self::MONEY_COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE NUMERIC(20,{$scale})");
            }
        }
    }
};
