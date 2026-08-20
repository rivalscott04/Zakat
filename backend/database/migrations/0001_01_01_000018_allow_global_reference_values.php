<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE zakat_reference_values ALTER COLUMN organization_id DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DELETE FROM zakat_reference_values WHERE organization_id IS NULL');
        DB::statement('ALTER TABLE zakat_reference_values ALTER COLUMN organization_id SET NOT NULL');
    }
};
