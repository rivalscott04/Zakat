<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * F-01 dan F-04 — integritas saldo fund di level database.
 *
 * Aplikasi tetap yang mencegah lebih dulu, tetapi constraint di sini menjadi
 * jaring terakhir sesuai CLAUDE.md §31.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Satu collection hanya boleh menghasilkan satu inflow.
        //
        // Sengaja tidak digeneralisasi ke seluruh source_type: `fund_transfer`
        // memang menghasilkan dua movement (keluar dan masuk) dengan source_id
        // sama, dan `distribution` boleh berkali-kali karena realisasi sebagian.
        DB::statement(
            "CREATE UNIQUE INDEX fund_movements_collection_source_unique
             ON fund_movements (organization_id, source_type, source_id)
             WHERE source_type = 'collection' AND source_id IS NOT NULL"
        );

        // Saldo riil tidak boleh negatif dalam keadaan apa pun.
        DB::statement('ALTER TABLE funds ADD CONSTRAINT funds_current_balance_non_negative CHECK (current_balance >= 0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE funds DROP CONSTRAINT IF EXISTS funds_current_balance_non_negative');
        DB::statement('DROP INDEX IF EXISTS fund_movements_collection_source_unique');
    }
};
