<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zakat_categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id');
            $table->string('code', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'code']);
        });
        Schema::create('zakat_types', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id');
            $table->ulid('zakat_category_id');
            $table->string('code', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('calculation_method', 30);
            $table->string('status', 20)->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('zakat_category_id')->references('id')->on('zakat_categories')->restrictOnDelete();
            $table->unique(['organization_id', 'code']);
        });
        Schema::create('zakat_rules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id');
            $table->ulid('zakat_type_id');
            $table->string('rule_code', 80)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('version');
            $table->string('status', 20)->default('draft');
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('zakat_type_id')->references('id')->on('zakat_types')->restrictOnDelete();
            $table->unique(['zakat_type_id', 'version']);
            $table->index(['organization_id', 'status', 'effective_from']);
        });
        Schema::create('zakat_rates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('zakat_rule_id');
            $table->string('rate_type', 20);
            $table->decimal('rate_value', 20, 8);
            $table->string('unit', 20);
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->timestamps();
            $table->foreign('zakat_rule_id')->references('id')->on('zakat_rules')->cascadeOnDelete();
        });
        Schema::create('zakat_nisabs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('zakat_rule_id');
            $table->string('nisab_type', 20);
            $table->string('reference_type', 20)->nullable();
            $table->decimal('reference_value', 20, 8)->nullable();
            $table->decimal('quantity', 20, 8)->nullable();
            $table->string('unit', 20)->nullable();
            $table->string('currency', 3)->nullable();
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->timestamps();
            $table->foreign('zakat_rule_id')->references('id')->on('zakat_rules')->cascadeOnDelete();
        });
        Schema::create('zakat_hauls', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('zakat_rule_id')->unique();
            $table->string('haul_type', 20);
            $table->unsignedInteger('duration')->nullable();
            $table->string('duration_unit', 10)->nullable();
            $table->string('calendar_type', 15)->nullable();
            $table->timestamps();
            $table->foreign('zakat_rule_id')->references('id')->on('zakat_rules')->cascadeOnDelete();
        });
        Schema::create('zakat_reference_values', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id');
            $table->string('reference_code', 50);
            $table->string('reference_type', 20);
            $table->decimal('value', 20, 8);
            $table->string('unit', 20);
            $table->string('source')->nullable();
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->index(['organization_id', 'reference_type', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zakat_reference_values');
        Schema::dropIfExists('zakat_hauls');
        Schema::dropIfExists('zakat_nisabs');
        Schema::dropIfExists('zakat_rates');
        Schema::dropIfExists('zakat_rules');
        Schema::dropIfExists('zakat_types');
        Schema::dropIfExists('zakat_categories');
    }
};
