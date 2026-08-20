<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mustahiks', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('organization_id');
            $t->string('mustahik_number', 30)->unique();
            $t->string('mustahik_type', 20)->default('individual');
            $t->string('full_name');
            $t->string('display_name');
            $t->string('gender', 20)->nullable();
            $t->date('birth_date')->nullable();
            $t->string('marital_status', 20)->nullable();
            $t->string('phone', 30)->nullable();
            $t->string('email')->nullable();
            $t->string('identity_type', 30)->nullable();
            $t->string('identity_number_hash', 64)->nullable();
            $t->string('status', 20)->default('active');
            $t->string('verification_status', 20)->default('unverified');
            $t->string('eligibility_status', 20)->default('incomplete');
            $t->date('registered_at');
            $t->ulid('registered_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $t->index(['organization_id', 'display_name', 'phone']);
            $t->unique(['organization_id', 'identity_number_hash']);
        });
        Schema::create('mustahik_identities', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('mustahik_id');
            $t->string('identity_type', 30);
            $t->text('identity_number_encrypted');
            $t->string('identity_number_hash', 64);
            $t->string('identity_name')->nullable();
            $t->string('verification_status', 20)->default('unverified');
            $t->dateTime('verified_at')->nullable();
            $t->ulid('verified_by')->nullable();
            $t->timestamps();
            $t->foreign('mustahik_id')->references('id')->on('mustahiks')->cascadeOnDelete();
            $t->unique(['mustahik_id', 'identity_type']);
            $t->unique('identity_number_hash');
        });
        Schema::create('mustahik_addresses', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('mustahik_id');
            $t->string('address_type', 20)->default('home');
            $t->text('address_line');
            $t->string('province_code', 20)->nullable();
            $t->string('regency_code', 20)->nullable();
            $t->string('district_code', 20)->nullable();
            $t->string('village_code', 20)->nullable();
            $t->string('postal_code', 10)->nullable();
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->boolean('is_primary')->default(false);
            $t->timestamps();
            $t->foreign('mustahik_id')->references('id')->on('mustahiks')->cascadeOnDelete();
        });
        Schema::create('mustahik_asnaf', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('mustahik_id');
            $t->string('asnaf_code', 30);
            $t->boolean('primary_asnaf')->default(false);
            $t->ulid('assessment_id')->nullable();
            $t->text('reason');
            $t->string('status', 20)->default('active');
            $t->date('effective_from');
            $t->date('effective_until')->nullable();
            $t->ulid('assigned_by')->nullable();
            $t->timestamps();
            $t->foreign('mustahik_id')->references('id')->on('mustahiks')->cascadeOnDelete();
            $t->index(['mustahik_id', 'asnaf_code', 'status']);
        });
        Schema::create('mustahik_profiles', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('mustahik_id')->unique();
            $t->string('education_level')->nullable();
            $t->string('occupation')->nullable();
            $t->string('employment_status')->nullable();
            $t->decimal('monthly_income', 20, 2)->nullable();
            $t->decimal('monthly_expense', 20, 2)->nullable();
            $t->string('housing_status')->nullable();
            $t->string('house_condition')->nullable();
            $t->jsonb('asset_summary')->nullable();
            $t->string('disability_status')->nullable();
            $t->text('health_condition_summary')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->foreign('mustahik_id')->references('id')->on('mustahiks')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        foreach (['mustahik_profiles', 'mustahik_asnaf', 'mustahik_addresses', 'mustahik_identities', 'mustahiks'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
