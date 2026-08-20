<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zakat_reference_values', function (Blueprint $table) {
            $table->string('name')->nullable()->after('reference_type');
            $table->timestamp('effective_at')->nullable()->after('source');
            $table->timestamp('expires_at')->nullable()->after('effective_at');
        });
        Schema::create('zakat_rule_parameters', function (Blueprint $table) {
            $table->ulid('id')->primary(); $table->ulid('zakat_rule_id'); $table->string('parameter_code', 60); $table->string('name'); $table->text('description')->nullable(); $table->string('data_type', 20); $table->boolean('is_required')->default(false); $table->jsonb('default_value')->nullable(); $table->jsonb('validation_rules')->nullable(); $table->unsignedInteger('sort_order')->default(0); $table->timestamps(); $table->foreign('zakat_rule_id')->references('id')->on('zakat_rules')->cascadeOnDelete(); $table->unique(['zakat_rule_id', 'parameter_code']);
        });
        Schema::create('zakat_fitrah_configurations', function (Blueprint $table) {
            $table->ulid('id')->primary(); $table->ulid('zakat_rule_id'); $table->string('staple_type', 20); $table->decimal('quantity', 20, 8); $table->string('unit', 20); $table->decimal('cash_equivalent', 20, 8)->nullable(); $table->string('currency', 3)->nullable(); $table->ulid('region_id')->nullable(); $table->date('effective_from'); $table->date('effective_until')->nullable(); $table->string('status', 20)->default('active'); $table->timestamps(); $table->foreign('zakat_rule_id')->references('id')->on('zakat_rules')->cascadeOnDelete(); $table->index(['zakat_rule_id', 'region_id']);
        });
        Schema::create('zakat_agriculture_configurations', function (Blueprint $table) {
            $table->ulid('id')->primary(); $table->ulid('zakat_rule_id'); $table->string('commodity_type', 30); $table->string('irrigation_type', 20); $table->decimal('minimum_quantity', 20, 8); $table->string('quantity_unit', 20); $table->decimal('rate', 20, 8); $table->timestamps(); $table->foreign('zakat_rule_id')->references('id')->on('zakat_rules')->cascadeOnDelete();
        });
        Schema::create('zakat_livestock_configurations', function (Blueprint $table) {
            $table->ulid('id')->primary(); $table->ulid('zakat_rule_id'); $table->string('livestock_type', 20); $table->decimal('minimum_quantity', 20, 8); $table->decimal('maximum_quantity', 20, 8)->nullable(); $table->decimal('zakat_quantity', 20, 8); $table->string('zakat_unit', 20); $table->text('description')->nullable(); $table->timestamps(); $table->foreign('zakat_rule_id')->references('id')->on('zakat_rules')->cascadeOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('zakat_livestock_configurations'); Schema::dropIfExists('zakat_agriculture_configurations'); Schema::dropIfExists('zakat_fitrah_configurations'); Schema::dropIfExists('zakat_rule_parameters'); Schema::table('zakat_reference_values', fn (Blueprint $table) => $table->dropColumn(['name', 'effective_at', 'expires_at'])); }
};
