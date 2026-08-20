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
        ];

        foreach ($codes as $code) {
            CodeRegistry::query()->updateOrCreate(['code' => $code['code']], $code + ['is_active' => true]);
        }
    }
}
