<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * F-08 — NIK mustahik hanya unik di dalam satu organisasi.
 *
 * Unique global sebelumnya membuat organisasi lain tahu bahwa sebuah NIK sudah
 * terdaftar di tempat lain (Core PRD §23 dan §27), sekaligus memblokir mereka
 * mendata penerimanya sendiri. Satu orang wajar menerima bantuan dari lebih dari
 * satu lembaga zakat.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('mustahik_identities', 'organization_id')) {
            Schema::table('mustahik_identities', function (Blueprint $table) {
                $table->ulid('organization_id')->nullable()->after('id');
            });
        }

        // Isi dari mustahik induknya untuk data yang sudah ada.
        DB::statement('UPDATE mustahik_identities SET organization_id = m.organization_id FROM mustahiks m WHERE m.id = mustahik_identities.mustahik_id');

        Schema::table('mustahik_identities', function (Blueprint $table) {
            $table->ulid('organization_id')->nullable(false)->change();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
        });

        // Unique lama dibuat sebagai table constraint, jadi harus dilepas lewat
        // ALTER TABLE, bukan DROP INDEX.
        DB::statement('ALTER TABLE mustahik_identities DROP CONSTRAINT IF EXISTS mustahik_identities_identity_number_hash_unique');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS mustahik_identities_org_hash_unique ON mustahik_identities (organization_id, identity_number_hash)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS mustahik_identities_org_hash_unique');

        Schema::table('mustahik_identities', function (Blueprint $table) {
            $table->dropConstrainedForeignKey('organization_id');
        });

        DB::statement('ALTER TABLE mustahik_identities DROP COLUMN IF EXISTS organization_id');

        DB::statement('CREATE UNIQUE INDEX mustahik_identities_identity_number_hash_unique ON mustahik_identities (identity_number_hash)');
    }
};
