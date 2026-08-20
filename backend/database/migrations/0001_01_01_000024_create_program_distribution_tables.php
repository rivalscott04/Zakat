<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_categories', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('organization_id');
            $t->string('category_code', 30);
            $t->string('name');
            $t->text('description')->nullable();
            $t->ulid('parent_id')->nullable();
            $t->string('status', 20)->default('active');
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $t->unique(['organization_id', 'category_code']);
        });
        Schema::create('programs', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('organization_id');
            $t->string('program_code', 30)->unique();
            $t->string('name');
            $t->string('short_name')->nullable();
            $t->text('description')->nullable();
            $t->ulid('category_id')->nullable();
            $t->string('program_type', 30);
            $t->date('start_date')->nullable();
            $t->date('end_date')->nullable();
            $t->unsignedInteger('target_beneficiary')->nullable();
            $t->unsignedInteger('capacity_limit')->nullable();
            $t->string('status', 25)->default('draft');
            $t->string('visibility', 20)->default('internal');
            $t->ulid('created_by')->nullable();
            $t->dateTime('archived_at')->nullable();
            $t->timestamps();
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $t->foreign('category_id')->references('id')->on('program_categories')->nullOnDelete();
            $t->index(['organization_id', 'status']);
        });
        Schema::create('program_budgets', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('organization_id');
            $t->ulid('program_id');
            $t->ulid('fund_id');
            $t->decimal('budget_amount', 20, 2);
            $t->string('currency', 3)->default('IDR');
            $t->decimal('allocated_amount', 20, 2)->default(0);
            $t->decimal('committed_amount', 20, 2)->default(0);
            $t->decimal('disbursed_amount', 20, 2)->default(0);
            $t->decimal('remaining_amount', 20, 2)->default(0);
            $t->string('status', 20)->default('draft');
            $t->timestamps();
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $t->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
            $t->foreign('fund_id')->references('id')->on('funds')->restrictOnDelete();
        });
        Schema::create('program_enrollments', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('organization_id');
            $t->ulid('program_id');
            $t->ulid('mustahik_id');
            $t->string('enrollment_number', 30)->unique();
            $t->string('eligibility_result', 30)->default('pending');
            $t->ulid('assessment_id')->nullable();
            $t->dateTime('enrolled_at');
            $t->ulid('enrolled_by')->nullable();
            $t->ulid('approved_by')->nullable();
            $t->dateTime('approved_at')->nullable();
            $t->string('status', 20)->default('pending');
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $t->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
            $t->foreign('mustahik_id')->references('id')->on('mustahiks')->restrictOnDelete();
            $t->index(['program_id', 'mustahik_id', 'status']);
        });
        Schema::create('distribution_requests', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('organization_id');
            $t->string('request_number', 30)->unique();
            $t->ulid('mustahik_id');
            $t->ulid('program_id')->nullable();
            $t->ulid('assessment_id')->nullable();
            $t->ulid('fund_id');
            $t->string('distribution_type', 30);
            $t->decimal('requested_amount', 20, 2);
            $t->string('currency', 3)->default('IDR');
            $t->text('reason');
            $t->string('priority', 10)->default('normal');
            $t->ulid('requested_by')->nullable();
            $t->dateTime('requested_at');
            $t->string('status', 20)->default('pending');
            $t->timestamps();
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $t->foreign('mustahik_id')->references('id')->on('mustahiks')->restrictOnDelete();
            $t->foreign('program_id')->references('id')->on('programs')->nullOnDelete();
            $t->foreign('fund_id')->references('id')->on('funds')->restrictOnDelete();
        });
        Schema::create('distributions', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('organization_id');
            $t->string('distribution_number', 30)->unique();
            $t->string('distribution_type', 30);
            $t->string('source_type', 20);
            $t->ulid('program_id')->nullable();
            $t->ulid('program_enrollment_id')->nullable();
            $t->ulid('mustahik_id');
            $t->ulid('assessment_id')->nullable();
            $t->ulid('fund_id');
            $t->string('currency', 3)->default('IDR');
            $t->decimal('requested_amount', 20, 2);
            $t->decimal('approved_amount', 20, 2)->default(0);
            $t->decimal('distributed_amount', 20, 2)->default(0);
            $t->date('distribution_date')->nullable();
            $t->date('scheduled_date')->nullable();
            $t->string('status', 25)->default('draft');
            $t->string('priority', 10)->default('normal');
            $t->text('description')->nullable();
            $t->ulid('created_by')->nullable();
            $t->timestamps();
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $t->foreign('program_id')->references('id')->on('programs')->nullOnDelete();
            $t->foreign('mustahik_id')->references('id')->on('mustahiks')->restrictOnDelete();
            $t->foreign('fund_id')->references('id')->on('funds')->restrictOnDelete();
            $t->index(['organization_id', 'status']);
        });
        Schema::create('distribution_items', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('distribution_id');
            $t->string('item_code', 40);
            $t->string('item_name');
            $t->text('description')->nullable();
            $t->decimal('quantity', 12, 2)->default(1);
            $t->string('unit', 20)->default('unit');
            $t->decimal('unit_value', 20, 2)->default(0);
            $t->decimal('total_value', 20, 2)->default(0);
            $t->timestamps();
            $t->foreign('distribution_id')->references('id')->on('distributions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        foreach (['distribution_items', 'distributions', 'distribution_requests', 'program_enrollments', 'program_budgets', 'programs', 'program_categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
