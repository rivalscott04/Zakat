<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** PRD 03 — data inti, profil, privacy, tagging, duplicate review, dan merge Muzaki. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muzakis', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id');
            $table->string('business_number')->unique();
            $table->string('muzaki_type', 20);
            $table->string('status', 20)->default('lead');
            $table->string('display_name');
            $table->string('registration_source', 20)->default('manual');
            $table->timestamp('registered_at');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'muzaki_type']);
            $table->index(['organization_id', 'display_name']);
        });

        Schema::create('muzaki_individual_profiles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('muzaki_id')->unique();
            $table->string('full_name');
            $table->string('title_prefix', 30)->nullable();
            $table->string('title_suffix', 30)->nullable();
            $table->string('gender', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('nationality', 2)->nullable();
            $table->string('occupation')->nullable();
            $table->string('education_level')->nullable();
            $table->timestamps();
            $table->foreign('muzaki_id')->references('id')->on('muzakis')->cascadeOnDelete();
        });

        Schema::create('muzaki_organization_profiles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('muzaki_id')->unique();
            $table->string('legal_name');
            $table->string('registration_number')->nullable();
            $table->string('industry')->nullable();
            $table->string('representative_name')->nullable();
            $table->string('representative_position')->nullable();
            $table->timestamps();
            $table->foreign('muzaki_id')->references('id')->on('muzakis')->cascadeOnDelete();
        });

        Schema::create('muzaki_representatives', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('muzaki_id');
            $table->string('name');
            $table->string('position')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->foreign('muzaki_id')->references('id')->on('muzakis')->cascadeOnDelete();
        });
        DB::statement('CREATE UNIQUE INDEX muzaki_representatives_primary_unique ON muzaki_representatives (muzaki_id) WHERE is_primary');

        Schema::create('muzaki_identities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('muzaki_id');
            $table->string('identity_type', 20);
            $table->text('identity_number_encrypted');
            $table->string('identity_number_hash', 64);
            $table->string('issued_country', 2)->nullable();
            $table->string('verification_status', 20)->default('unverified');
            $table->timestamp('verified_at')->nullable();
            $table->ulid('verified_by')->nullable();
            $table->timestamps();
            $table->foreign('muzaki_id')->references('id')->on('muzakis')->cascadeOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['muzaki_id', 'identity_type']);
            $table->index(['identity_number_hash']);
        });

        Schema::create('muzaki_contacts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('muzaki_id');
            $table->string('contact_type', 20);
            $table->string('label')->nullable();
            $table->text('value_encrypted');
            $table->string('value_hash', 64);
            $table->boolean('is_primary')->default(false);
            $table->string('verification_status', 20)->default('unverified');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('muzaki_id')->references('id')->on('muzakis')->cascadeOnDelete();
            $table->index(['value_hash']);
        });
        DB::statement('CREATE UNIQUE INDEX muzaki_contacts_primary_unique ON muzaki_contacts (muzaki_id, contact_type) WHERE is_primary AND deleted_at IS NULL');

        Schema::create('muzaki_addresses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('muzaki_id');
            $table->string('address_type', 20);
            $table->string('label')->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            // Master reference belum tersedia. ULID disimpan untuk FK saat module
            // reference data diaktifkan; API hanya menerima ULID yang valid.
            $table->ulid('country_id')->nullable();
            $table->ulid('province_id')->nullable();
            $table->ulid('city_id')->nullable();
            $table->ulid('district_id')->nullable();
            $table->ulid('village_id')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('muzaki_id')->references('id')->on('muzakis')->cascadeOnDelete();
        });
        DB::statement('CREATE UNIQUE INDEX muzaki_addresses_primary_unique ON muzaki_addresses (muzaki_id) WHERE is_primary AND deleted_at IS NULL');

        Schema::create('muzaki_family_members', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('muzaki_id');
            $table->string('name');
            $table->string('relationship', 20);
            $table->date('birth_date')->nullable();
            $table->string('gender', 20)->nullable();
            $table->boolean('is_head')->default(false);
            $table->timestamps();
            $table->foreign('muzaki_id')->references('id')->on('muzakis')->cascadeOnDelete();
        });
        DB::statement('CREATE UNIQUE INDEX muzaki_family_members_head_unique ON muzaki_family_members (muzaki_id) WHERE is_head');

        Schema::create('muzaki_preferences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('muzaki_id')->unique();
            $table->boolean('allow_email')->default(false);
            $table->boolean('allow_sms')->default(false);
            $table->boolean('allow_whatsapp')->default(false);
            $table->string('communication_preference', 20)->nullable();
            $table->string('public_visibility', 20)->default('private');
            $table->string('receipt_delivery_method', 30)->nullable();
            $table->timestamps();
            $table->foreign('muzaki_id')->references('id')->on('muzakis')->cascadeOnDelete();
        });

        Schema::create('muzaki_tags', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id');
            $table->string('code', 50);
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('muzaki_tag_assignments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('muzaki_id');
            $table->ulid('tag_id');
            $table->ulid('assigned_by')->nullable();
            $table->timestamp('assigned_at');
            $table->foreign('muzaki_id')->references('id')->on('muzakis')->cascadeOnDelete();
            $table->foreign('tag_id')->references('id')->on('muzaki_tags')->cascadeOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['muzaki_id', 'tag_id']);
        });

        Schema::create('muzaki_notes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('muzaki_id');
            $table->text('note');
            $table->string('visibility', 20)->default('internal');
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->foreign('muzaki_id')->references('id')->on('muzakis')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('muzaki_duplicate_reviews', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id');
            $table->ulid('source_muzaki_id');
            $table->ulid('candidate_muzaki_id');
            $table->unsignedSmallInteger('match_score');
            $table->jsonb('match_reasons');
            $table->string('status', 30)->default('pending');
            $table->ulid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('source_muzaki_id')->references('id')->on('muzakis')->cascadeOnDelete();
            $table->foreign('candidate_muzaki_id')->references('id')->on('muzakis')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['source_muzaki_id', 'candidate_muzaki_id']);
        });

        Schema::create('muzaki_merge_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id');
            $table->ulid('source_muzaki_id')->unique();
            $table->ulid('target_muzaki_id');
            $table->text('reason');
            $table->jsonb('source_snapshot');
            $table->jsonb('target_snapshot');
            $table->ulid('merged_by')->nullable();
            $table->timestamp('merged_at');
            $table->timestamp('created_at');
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('source_muzaki_id')->references('id')->on('muzakis')->restrictOnDelete();
            $table->foreign('target_muzaki_id')->references('id')->on('muzakis')->restrictOnDelete();
            $table->foreign('merged_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['organization_id', 'merged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muzaki_merge_logs');
        Schema::dropIfExists('muzaki_duplicate_reviews');
        Schema::dropIfExists('muzaki_notes');
        Schema::dropIfExists('muzaki_tag_assignments');
        Schema::dropIfExists('muzaki_tags');
        Schema::dropIfExists('muzaki_preferences');
        Schema::dropIfExists('muzaki_family_members');
        Schema::dropIfExists('muzaki_addresses');
        Schema::dropIfExists('muzaki_contacts');
        Schema::dropIfExists('muzaki_identities');
        Schema::dropIfExists('muzaki_representatives');
        Schema::dropIfExists('muzaki_organization_profiles');
        Schema::dropIfExists('muzaki_individual_profiles');
        Schema::dropIfExists('muzakis');
    }
};
