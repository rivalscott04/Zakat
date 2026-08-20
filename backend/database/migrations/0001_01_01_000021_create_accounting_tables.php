<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_periods', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('organization_id');
            $t->string('period_code', 6);
            $t->string('name');
            $t->date('start_date');
            $t->date('end_date');
            $t->string('status', 10)->default('open');
            $t->dateTime('closed_at')->nullable();
            $t->ulid('closed_by')->nullable();
            $t->timestamps();
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $t->unique(['organization_id', 'period_code']);
        });
        Schema::create('chart_of_accounts', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('organization_id');
            $t->string('account_code', 30);
            $t->string('account_name');
            $t->string('account_type', 20);
            $t->string('account_category', 30)->nullable();
            $t->ulid('parent_id')->nullable();
            $t->string('normal_balance', 6);
            $t->boolean('is_postable')->default(true);
            $t->string('status', 20)->default('active');
            $t->text('description')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $t->unique(['organization_id', 'account_code']);
        });
        Schema::create('journal_entries', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('organization_id');
            $t->string('journal_number', 30)->unique();
            $t->date('journal_date');
            $t->ulid('accounting_period_id');
            $t->string('journal_type', 20)->default('manual');
            $t->string('source_type', 40)->nullable();
            $t->ulid('source_id')->nullable();
            $t->string('reference_number', 100)->nullable();
            $t->text('description');
            $t->string('status', 20)->default('draft');
            $t->ulid('reversal_of_id')->nullable();
            $t->ulid('created_by')->nullable();
            $t->ulid('posted_by')->nullable();
            $t->dateTime('posted_at')->nullable();
            $t->timestamps();
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $t->foreign('accounting_period_id')->references('id')->on('accounting_periods')->restrictOnDelete();
            $t->index(['organization_id', 'journal_date', 'status']);
        });
        Schema::create('journal_lines', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('journal_entry_id');
            $t->unsignedInteger('line_number');
            $t->ulid('account_id');
            $t->text('description')->nullable();
            $t->decimal('debit_amount', 20, 2)->default(0);
            $t->decimal('credit_amount', 20, 2)->default(0);
            $t->string('currency', 3)->default('IDR');
            $t->timestamps();
            $t->foreign('journal_entry_id')->references('id')->on('journal_entries')->cascadeOnDelete();
            $t->foreign('account_id')->references('id')->on('chart_of_accounts')->restrictOnDelete();
            $t->unique(['journal_entry_id', 'line_number']);
        });
        Schema::create('accounting_events', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('organization_id');
            $t->string('event_type', 50);
            $t->string('source_type', 50);
            $t->ulid('source_id');
            $t->string('reference_number', 100)->nullable();
            $t->date('event_date');
            $t->jsonb('payload')->nullable();
            $t->string('status', 20)->default('pending');
            $t->dateTime('processed_at')->nullable();
            $t->ulid('journal_entry_id')->nullable();
            $t->timestamps();
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $t->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
            $t->unique(['organization_id', 'event_type', 'source_type', 'source_id']);
        });
        Schema::create('accounting_rules', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('organization_id');
            $t->string('rule_code', 60);
            $t->string('name');
            $t->string('event_type', 50);
            $t->ulid('debit_account_id');
            $t->ulid('credit_account_id');
            $t->jsonb('condition_data')->nullable();
            $t->unsignedInteger('priority')->default(0);
            $t->string('status', 20)->default('active');
            $t->date('effective_from');
            $t->date('effective_until')->nullable();
            $t->timestamps();
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $t->foreign('debit_account_id')->references('id')->on('chart_of_accounts')->restrictOnDelete();
            $t->foreign('credit_account_id')->references('id')->on('chart_of_accounts')->restrictOnDelete();
            $t->unique(['organization_id', 'rule_code']);
        });
    }

    public function down(): void
    {
        foreach (['accounting_rules', 'accounting_events', 'journal_lines', 'journal_entries', 'chart_of_accounts', 'accounting_periods'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
