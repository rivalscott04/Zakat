<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_templates', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('organization_id');
            $t->string('template_code', 40);
            $t->string('name');
            $t->text('description')->nullable();
            $t->string('assessment_type', 30)->default('initial');
            $t->string('mustahik_type', 30)->nullable();
            $t->unsignedInteger('version')->default(1);
            $t->string('status', 20)->default('draft');
            $t->date('effective_from')->nullable();
            $t->date('effective_until')->nullable();
            $t->jsonb('schema')->nullable();
            $t->timestamps();
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $t->unique(['organization_id', 'template_code', 'version']);
        });

        Schema::create('assessment_requests', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('organization_id');
            $t->string('request_number', 30)->unique();
            $t->ulid('mustahik_id');
            $t->string('assessment_type', 30);
            $t->string('priority', 10)->default('normal');
            $t->text('reason')->nullable();
            $t->ulid('requested_by')->nullable();
            $t->dateTime('requested_at');
            $t->date('due_date')->nullable();
            $t->string('status', 20)->default('draft');
            $t->ulid('assessor_id')->nullable();
            $t->dateTime('assigned_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $t->foreign('mustahik_id')->references('id')->on('mustahiks')->restrictOnDelete();
            $t->index(['organization_id', 'status', 'priority']);
        });

        Schema::create('assessments', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('organization_id');
            $t->string('assessment_number', 30)->unique();
            $t->ulid('assessment_request_id');
            $t->ulid('mustahik_id');
            $t->ulid('template_id')->nullable();
            $t->unsignedInteger('template_version')->nullable();
            $t->string('assessment_type', 30);
            $t->ulid('assessor_id')->nullable();
            $t->date('assessment_date')->nullable();
            $t->dateTime('started_at')->nullable();
            $t->dateTime('submitted_at')->nullable();
            $t->dateTime('approved_at')->nullable();
            $t->string('status', 20)->default('draft');
            $t->decimal('total_score', 12, 2)->nullable();
            $t->string('result', 40)->nullable();
            $t->string('recommendation', 40)->nullable();
            $t->text('recommendation_reason')->nullable();
            $t->text('review_notes')->nullable();
            $t->ulid('previous_assessment_id')->nullable();
            $t->timestamps();
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $t->foreign('assessment_request_id')->references('id')->on('assessment_requests')->restrictOnDelete();
            $t->foreign('mustahik_id')->references('id')->on('mustahiks')->restrictOnDelete();
            $t->foreign('template_id')->references('id')->on('assessment_templates')->nullOnDelete();
            $t->index(['organization_id', 'status']);
            $t->index('previous_assessment_id');
        });

        Schema::create('assessment_answers', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('assessment_id');
            $t->ulid('question_id')->nullable();
            $t->string('question_code', 80);
            $t->text('answer_value')->nullable();
            $t->jsonb('answer_data')->nullable();
            $t->decimal('score', 12, 2)->nullable();
            $t->text('notes')->nullable();
            $t->jsonb('question_snapshot')->nullable();
            $t->timestamps();
            $t->foreign('assessment_id')->references('id')->on('assessments')->cascadeOnDelete();
            $t->unique(['assessment_id', 'question_code']);
        });

        Schema::create('assessment_reviews', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('assessment_id');
            $t->ulid('reviewer_id')->nullable();
            $t->string('decision', 15);
            $t->text('notes')->nullable();
            $t->dateTime('reviewed_at');
            $t->timestamps();
            $t->foreign('assessment_id')->references('id')->on('assessments')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        foreach (['assessment_reviews', 'assessment_answers', 'assessments', 'assessment_requests', 'assessment_templates'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
