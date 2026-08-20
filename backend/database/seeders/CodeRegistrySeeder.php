<?php

namespace Database\Seeders;

use App\Models\CodeRegistry;
use Illuminate\Database\Seeder;

/** PRD 00 §9 dan §10 — business code wajib terdaftar sebelum dipakai. */
class CodeRegistrySeeder extends Seeder
{
    public function run(): void
    {
        $codes = [
            ['code' => 'ORG', 'name' => 'Organization', 'entity_type' => 'organizations', 'module' => 'organization'],
            ['code' => 'AML', 'name' => 'Amil', 'entity_type' => 'amils', 'module' => 'organization'],
            ['code' => 'MZK', 'name' => 'Muzaki', 'entity_type' => 'muzakis', 'module' => 'muzaki'],
            ['code' => 'ZKC', 'name' => 'Zakat Calculation', 'entity_type' => 'zakat_calculations', 'module' => 'zakat'],
            ['code' => 'COL', 'name' => 'Collection', 'entity_type' => 'collections', 'module' => 'collection'],
            ['code' => 'FND', 'name' => 'Fund Movement', 'entity_type' => 'fund_movements', 'module' => 'fund'],
            ['code' => 'ALC', 'name' => 'Fund Allocation', 'entity_type' => 'fund_allocations', 'module' => 'fund'],
            ['code' => 'RSV', 'name' => 'Fund Reservation', 'entity_type' => 'fund_reservations', 'module' => 'fund'],
            ['code' => 'FTR', 'name' => 'Fund Transfer', 'entity_type' => 'fund_transfers', 'module' => 'fund'],
            ['code' => 'REC', 'name' => 'Fund Reconciliation', 'entity_type' => 'fund_reconciliations', 'module' => 'fund'],
            ['code' => 'JRN', 'name' => 'Journal Entry', 'entity_type' => 'journal_entries', 'module' => 'accounting'],
            ['code' => 'MSH', 'name' => 'Mustahik', 'entity_type' => 'mustahiks', 'module' => 'mustahik'],
        ];

        foreach ($codes as $code) {
            CodeRegistry::query()->updateOrCreate(['code' => $code['code']], $code + ['is_active' => true]);
        }
    }
}
