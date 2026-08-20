<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** PRD 02 §22 dan §23 — alamat dan kontak organisasi. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_addresses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id');

            $table->string('label')->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();

            $table->string('country_code', 2)->default('ID');
            $table->string('province_code', 10)->nullable();
            $table->string('city_code', 10)->nullable();
            $table->string('district_code', 10)->nullable();
            $table->string('village_code', 10)->nullable();
            $table->string('postal_code', 10)->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
        });

        // PRD 02 §22 — hanya satu alamat utama per organisasi, dijaga di level database.
        DB::statement('CREATE UNIQUE INDEX organization_addresses_primary_unique ON organization_addresses (organization_id) WHERE is_primary');

        Schema::create('organization_contacts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id');

            $table->string('type', 20);
            $table->string('label')->nullable();
            $table->string('value');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->index(['organization_id', 'type']);
        });

        DB::statement('CREATE UNIQUE INDEX organization_contacts_primary_unique ON organization_contacts (organization_id, type) WHERE is_primary');
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_contacts');
        Schema::dropIfExists('organization_addresses');
    }
};
