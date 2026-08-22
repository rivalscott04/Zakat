<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PRD 17C §6 — melengkapi audit_logs dengan field yang diminta modul 17.
 *
 * Tabel dibuat modul 00 dengan bentuk minimal yang cukup untuk mencatat. Modul
 * 17 menambah kebutuhan penelusuran: penomoran, kategori, modul asal, tingkat
 * kepentingan, dan waktu kejadian yang terpisah dari waktu penyimpanan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('audit_number', 30)->nullable()->after('id');
            $table->string('event_name', 120)->nullable()->after('audit_number');
            $table->string('event_category', 30)->nullable()->after('event_name');
            $table->string('module_code', 30)->nullable()->after('event_category');
            $table->string('entity_reference', 120)->nullable()->after('entity_id');
            $table->text('description')->nullable()->after('entity_reference');
            $table->string('actor_type', 20)->default('USER')->after('actor_name');
            $table->string('severity', 20)->default('INFO')->after('actor_type');
            $table->dateTime('occurred_at')->nullable()->after('user_agent');
        });

        // PRD 17H §15 dan §16 memakai penamaan old_values dan new_values,
        // PRD 17I memakai metadata. Hanya AuditService yang menyentuh kolom ini.
        DB::statement('ALTER TABLE audit_logs RENAME COLUMN "before" TO old_values');
        DB::statement('ALTER TABLE audit_logs RENAME COLUMN "after" TO new_values');
        DB::statement('ALTER TABLE audit_logs RENAME COLUMN context TO metadata');

        // Baris lama tetap dapat ditelusuri walau tanpa penomoran.
        DB::statement('UPDATE audit_logs SET occurred_at = created_at, event_name = action WHERE occurred_at IS NULL');

        DB::statement('CREATE UNIQUE INDEX audit_logs_audit_number_unique ON audit_logs (audit_number) WHERE audit_number IS NOT NULL');

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['organization_id', 'event_category', 'occurred_at']);
            $table->index(['organization_id', 'severity']);
            $table->index('request_id');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'event_category', 'occurred_at']);
            $table->dropIndex(['organization_id', 'severity']);
            $table->dropIndex(['request_id']);
        });

        DB::statement('DROP INDEX IF EXISTS audit_logs_audit_number_unique');
        DB::statement('ALTER TABLE audit_logs RENAME COLUMN metadata TO context');
        DB::statement('ALTER TABLE audit_logs RENAME COLUMN new_values TO "after"');
        DB::statement('ALTER TABLE audit_logs RENAME COLUMN old_values TO "before"');

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn([
                'audit_number', 'event_name', 'event_category', 'module_code',
                'entity_reference', 'description', 'actor_type', 'severity', 'occurred_at',
            ]);
        });
    }
};
