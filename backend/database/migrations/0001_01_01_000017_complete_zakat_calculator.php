<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zakat_calculations', function (Blueprint $table) {
            $table->date('valid_until')->nullable()->after('calculation_date');
            $table->string('formula_code', 80)->nullable()->after('zakat_rule_id');
            $table->unsignedSmallInteger('formula_version')->nullable()->after('formula_code');
            $table->ulid('parent_calculation_id')->nullable()->after('created_by');
            $table->foreign('parent_calculation_id')->references('id')->on('zakat_calculations')->nullOnDelete();
        });
        Schema::table('zakat_reference_values', fn (Blueprint $table) => $table->ulid('region_id')->nullable()->after('organization_id'));
        Schema::table('zakat_calculation_adjustments', function (Blueprint $table) {
            $table->ulid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });
        Schema::create('zakat_formula_definitions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('zakat_rule_id');
            $table->string('formula_code', 80);
            $table->unsignedSmallInteger('formula_version');
            $table->string('formula_type', 30);
            $table->string('expression', 255);
            $table->jsonb('input_schema');
            $table->jsonb('output_schema');
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->foreign('zakat_rule_id')->references('id')->on('zakat_rules')->cascadeOnDelete();
            $table->unique(['zakat_rule_id', 'formula_code', 'formula_version']);
        });
        Schema::create('zakat_calculation_versions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('calculation_id');
            $table->unsignedSmallInteger('version');
            $table->ulid('parent_calculation_id')->nullable();
            $table->text('reason');
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->foreign('calculation_id')->references('id')->on('zakat_calculations')->cascadeOnDelete();
            $table->foreign('parent_calculation_id')->references('id')->on('zakat_calculations')->nullOnDelete();
            $table->unique(['calculation_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zakat_calculation_versions');
        Schema::dropIfExists('zakat_formula_definitions');
        Schema::table('zakat_calculation_adjustments', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approved_by', 'approved_at']);
        });
        Schema::table('zakat_reference_values', fn (Blueprint $table) => $table->dropColumn('region_id'));
        Schema::table('zakat_calculations', function (Blueprint $table) {
            $table->dropForeign(['parent_calculation_id']);
            $table->dropColumn(['valid_until', 'formula_code', 'formula_version', 'parent_calculation_id']);
        });
    }
};
