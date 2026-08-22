<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CodeRegistrySeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            ReportCatalogSeeder::class,
        ]);
    }
}
