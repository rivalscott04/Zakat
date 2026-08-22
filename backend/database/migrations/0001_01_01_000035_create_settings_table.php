<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PRD 01 §14 dan §20 — System Settings. Satu tabel menampung dua scope:
 * organization_id NULL berarti System Setting global, terisi berarti
 * Organization Setting yang menimpanya (PRD 02 §24).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->jsonb('value');
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // NULL tidak pernah sama dengan NULL di UNIQUE biasa, jadi baris global
        // butuh index parsialnya sendiri agar tetap satu baris per key.
        Schema::table('settings', function (Blueprint $table) {
            $table->unique(['organization_id', 'key']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX settings_global_key_unique ON settings (key) WHERE organization_id IS NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
