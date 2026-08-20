<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zakat_calculations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id');
            $table->string('calculation_number', 30)->unique();
            $table->string('business_number', 30)->nullable()->unique();
            $table->ulid('muzaki_id');
            $table->ulid('zakat_type_id');
            $table->ulid('zakat_rule_id');
            $table->unsignedSmallInteger('rule_version');
            $table->date('calculation_date');
            $table->string('status', 20)->default('draft');
            $table->string('eligibility_status', 20)->default('incomplete');
            $table->decimal('gross_amount', 20, 8)->default(0);
            $table->decimal('deduction_amount', 20, 8)->default(0);
            $table->decimal('net_amount', 20, 8)->default(0);
            $table->decimal('nisab_amount', 20, 8)->nullable();
            $table->decimal('zakat_rate', 20, 8)->nullable();
            $table->decimal('zakat_amount', 20, 8)->default(0);
            $table->string('currency', 3)->default('IDR');
            $table->jsonb('result_data')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('muzaki_id')->references('id')->on('muzakis')->restrictOnDelete();
            $table->foreign('zakat_type_id')->references('id')->on('zakat_types')->restrictOnDelete();
            $table->foreign('zakat_rule_id')->references('id')->on('zakat_rules')->restrictOnDelete();
            $table->index(['organization_id', 'status', 'calculation_date']);
        });
        Schema::create('zakat_calculation_inputs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('calculation_id');
            $table->string('parameter_code', 60);
            $table->jsonb('value');
            $table->jsonb('normalized_value')->nullable();
            $table->string('unit', 20)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('source', 20)->default('manual');
            $table->timestamps();
            $table->foreign('calculation_id')->references('id')->on('zakat_calculations')->cascadeOnDelete();
            $table->unique(['calculation_id', 'parameter_code']);
        });
        Schema::create('zakat_calculation_snapshots', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('calculation_id')->unique();
            $table->jsonb('zakat_type_snapshot');
            $table->jsonb('zakat_rule_snapshot');
            $table->jsonb('nisab_snapshot')->nullable();
            $table->jsonb('haul_snapshot')->nullable();
            $table->jsonb('rate_snapshot')->nullable();
            $table->jsonb('reference_value_snapshot')->nullable();
            $table->jsonb('parameter_snapshot')->nullable();
            $table->jsonb('formula_snapshot');
            $table->jsonb('result_snapshot');
            $table->timestamps();
            $table->foreign('calculation_id')->references('id')->on('zakat_calculations')->cascadeOnDelete();
        });
        Schema::create('zakat_calculation_adjustments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('calculation_id');
            $table->string('adjustment_type', 20);
            $table->decimal('original_amount', 20, 8);
            $table->decimal('adjustment_amount', 20, 8);
            $table->decimal('final_amount', 20, 8);
            $table->text('reason');
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->foreign('calculation_id')->references('id')->on('zakat_calculations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zakat_calculation_adjustments');
        Schema::dropIfExists('zakat_calculation_snapshots');
        Schema::dropIfExists('zakat_calculation_inputs');
        Schema::dropIfExists('zakat_calculations');
    }
};
