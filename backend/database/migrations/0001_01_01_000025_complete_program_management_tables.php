<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $t) {
            $t->boolean('waitlist_enabled')->default(true);
        });
        Schema::create('program_periods', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('program_id');
            $t->string('period_code', 30);
            $t->string('name');
            $t->date('start_date');
            $t->date('end_date');
            $t->unsignedInteger('target_beneficiary')->nullable();
            $t->string('status', 20)->default('draft');
            $t->timestamps();
            $t->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
            $t->unique(['program_id', 'period_code']);
        });
        Schema::table('program_budgets', function (Blueprint $t) {
            $t->ulid('period_id')->nullable();
            $t->foreign('period_id')->references('id')->on('program_periods')->nullOnDelete();
        });
        Schema::create('program_funds', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('program_id');
            $t->ulid('fund_id');
            $t->unsignedInteger('priority')->default(0);
            $t->string('status', 20)->default('active');
            $t->timestamps();
            $t->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
            $t->foreign('fund_id')->references('id')->on('funds')->restrictOnDelete();
            $t->unique(['program_id', 'fund_id']);
        });
        Schema::create('program_eligibility_rules', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('program_id');
            $t->string('rule_code', 60);
            $t->string('rule_type', 30);
            $t->string('field', 100);
            $t->string('operator', 30);
            $t->jsonb('value');
            $t->decimal('weight', 8, 2)->default(1);
            $t->boolean('required')->default(false);
            $t->unsignedInteger('sort_order')->default(0);
            $t->string('status', 20)->default('active');
            $t->timestamps();
            $t->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
            $t->unique(['program_id', 'rule_code']);
        });
        Schema::create('program_eligibility_evaluations', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('program_id');
            $t->ulid('mustahik_id');
            $t->ulid('assessment_id')->nullable();
            $t->string('result', 30);
            $t->decimal('score', 8, 2)->default(0);
            $t->jsonb('matched_rules')->nullable();
            $t->text('override_reason')->nullable();
            $t->ulid('overridden_by')->nullable();
            $t->dateTime('evaluated_at');
            $t->ulid('evaluated_by')->nullable();
            $t->timestamps();
            $t->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
            $t->foreign('mustahik_id')->references('id')->on('mustahiks')->restrictOnDelete();
            $t->index(['program_id', 'mustahik_id', 'created_at']);
        });
        Schema::create('program_waitlists', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('program_id');
            $t->ulid('mustahik_id');
            $t->ulid('assessment_id')->nullable();
            $t->decimal('priority_score', 8, 2)->default(0);
            $t->unsignedInteger('position');
            $t->string('status', 20)->default('waiting');
            $t->dateTime('added_at');
            $t->dateTime('processed_at')->nullable();
            $t->timestamps();
            $t->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
            $t->foreign('mustahik_id')->references('id')->on('mustahiks')->restrictOnDelete();
            $t->unique(['program_id', 'mustahik_id', 'status']);
        });
        Schema::create('program_activities', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('program_id');
            $t->string('activity_code', 50);
            $t->string('name');
            $t->text('description')->nullable();
            $t->string('activity_type', 30);
            $t->date('start_date')->nullable();
            $t->date('end_date')->nullable();
            $t->string('location')->nullable();
            $t->string('status', 20)->default('draft');
            $t->timestamps();
            $t->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
            $t->unique(['program_id', 'activity_code']);
        });
        Schema::create('program_activity_participants', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('activity_id');
            $t->ulid('mustahik_id');
            $t->ulid('enrollment_id')->nullable();
            $t->string('attendance_status', 20)->default('registered');
            $t->string('participation_status', 20)->default('active');
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->foreign('activity_id')->references('id')->on('program_activities')->cascadeOnDelete();
            $t->foreign('mustahik_id')->references('id')->on('mustahiks')->restrictOnDelete();
            $t->unique(['activity_id', 'mustahik_id']);
        });
        Schema::create('program_targets', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('program_id');
            $t->string('target_type', 20);
            $t->string('name');
            $t->decimal('target_value', 20, 2);
            $t->decimal('current_value', 20, 2)->default(0);
            $t->string('unit', 30);
            $t->ulid('period_id')->nullable();
            $t->timestamps();
            $t->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
            $t->foreign('period_id')->references('id')->on('program_periods')->nullOnDelete();
        });
        Schema::create('program_outputs', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('program_id');
            $t->string('output_code', 60);
            $t->string('name');
            $t->decimal('target_value', 20, 2);
            $t->decimal('actual_value', 20, 2)->default(0);
            $t->string('unit', 30);
            $t->ulid('period_id')->nullable();
            $t->string('status', 20)->default('active');
            $t->timestamps();
            $t->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
            $t->unique(['program_id', 'output_code']);
        });
        Schema::create('program_outcomes', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('program_id');
            $t->string('outcome_code', 60);
            $t->string('name');
            $t->text('description')->nullable();
            $t->text('measurement_method')->nullable();
            $t->decimal('target_value', 20, 2);
            $t->decimal('actual_value', 20, 2)->default(0);
            $t->string('unit', 30);
            $t->date('measurement_date')->nullable();
            $t->string('status', 20)->default('active');
            $t->timestamps();
            $t->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
            $t->unique(['program_id', 'outcome_code']);
        });
        Schema::create('program_budget_commitments', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('program_id');
            $t->ulid('program_budget_id');
            $t->ulid('enrollment_id')->nullable();
            $t->ulid('distribution_id')->nullable();
            $t->decimal('amount', 20, 2);
            $t->string('currency', 3)->default('IDR');
            $t->string('status', 20)->default('committed');
            $t->text('reason')->nullable();
            $t->ulid('created_by')->nullable();
            $t->dateTime('created_at');
            $t->dateTime('updated_at')->nullable();
            $t->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
            $t->foreign('program_budget_id')->references('id')->on('program_budgets')->cascadeOnDelete();
            $t->foreign('enrollment_id')->references('id')->on('program_enrollments')->nullOnDelete();
            $t->index(['program_id', 'status']);
        });
    }

    public function down(): void
    {
        foreach (['program_budget_commitments', 'program_outcomes', 'program_outputs', 'program_targets', 'program_activity_participants', 'program_activities', 'program_waitlists', 'program_eligibility_evaluations', 'program_eligibility_rules', 'program_funds', 'program_periods'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('program_budgets', function (Blueprint $t) {
            $t->dropForeign(['period_id']);
            $t->dropColumn('period_id');
        });
        Schema::table('programs', function (Blueprint $t) {
            $t->dropColumn('waitlist_enabled');
        });
    }
};
