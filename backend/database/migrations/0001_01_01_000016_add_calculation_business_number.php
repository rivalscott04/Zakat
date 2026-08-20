<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('zakat_calculations', 'business_number')) {
            return;
        }

        Schema::table('zakat_calculations', function (Blueprint $table) {
            $table->string('business_number', 30)->nullable()->unique();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('zakat_calculations', 'business_number')) {
            return;
        }

        Schema::table('zakat_calculations', fn (Blueprint $table) => $table->dropUnique(['business_number']));
        Schema::table('zakat_calculations', fn (Blueprint $table) => $table->dropColumn('business_number'));
    }
};
