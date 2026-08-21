<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->ulid('id')->primary(); $table->ulid('organization_id');
            $table->string('account_code', 30); $table->string('bank_name'); $table->string('account_name');
            $table->text('account_number_encrypted'); $table->string('account_number_masked', 40);
            $table->char('currency', 3)->default('IDR'); $table->decimal('opening_balance', 18, 2)->default(0); $table->decimal('current_balance', 18, 2)->default(0);
            $table->string('status', 20)->default('ACTIVE'); $table->timestamps();
            $table->unique(['organization_id','account_code']); $table->index(['organization_id','status']);
        });
        Schema::create('bank_statements', function (Blueprint $table) {
            $table->ulid('id')->primary(); $table->ulid('organization_id'); $table->ulid('bank_account_id');
            $table->string('statement_number', 30); $table->date('period_start'); $table->date('period_end');
            $table->decimal('opening_balance',18,2)->default(0); $table->decimal('closing_balance',18,2)->default(0); $table->unsignedInteger('transaction_count')->default(0);
            $table->string('status',20)->default('DRAFT'); $table->ulid('imported_by')->nullable(); $table->timestamp('imported_at')->nullable(); $table->timestamps();
            $table->unique(['organization_id','statement_number']); $table->index(['organization_id','bank_account_id','period_start']);
        });
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary(); $table->ulid('organization_id'); $table->ulid('bank_statement_id'); $table->ulid('bank_account_id');
            $table->string('transaction_reference',80); $table->date('transaction_date'); $table->date('value_date')->nullable(); $table->text('description')->nullable();
            $table->decimal('debit_amount',18,2)->default(0); $table->decimal('credit_amount',18,2)->default(0); $table->decimal('balance',18,2)->nullable(); $table->char('currency',3)->default('IDR');
            $table->string('counterparty_name')->nullable(); $table->string('counterparty_account')->nullable(); $table->jsonb('raw_data')->nullable();
            $table->string('match_status',30)->default('UNMATCHED'); $table->string('duplicate_status',30)->default('NEW'); $table->timestamps();
            $table->unique(['organization_id','bank_account_id','transaction_reference']); $table->index(['organization_id','match_status']);
        });
        Schema::create('reconciliation_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary(); $table->ulid('organization_id'); $table->string('source_type',30); $table->ulid('source_id')->nullable();
            $table->string('transaction_reference',80); $table->date('transaction_date'); $table->decimal('amount',18,2); $table->char('currency',3)->default('IDR'); $table->string('direction',10); $table->string('status',30)->default('UNMATCHED'); $table->timestamps();
            $table->index(['organization_id','source_type','source_id']);
        });
        Schema::create('reconciliation_matches', function (Blueprint $table) {
            $table->ulid('id')->primary(); $table->ulid('organization_id'); $table->ulid('bank_transaction_id'); $table->ulid('reconciliation_transaction_id');
            $table->string('match_type',20); $table->decimal('matched_amount',18,2); $table->decimal('confidence_score',5,2)->default(0); $table->ulid('matched_by')->nullable(); $table->timestamp('matched_at')->nullable(); $table->string('status',30)->default('MATCHED'); $table->timestamps();
            $table->index(['organization_id','bank_transaction_id']);
        });
        Schema::create('reconciliation_sessions', function (Blueprint $table) {
            $table->ulid('id')->primary(); $table->ulid('organization_id'); $table->ulid('bank_account_id'); $table->string('session_number',30);
            $table->date('period_start'); $table->date('period_end'); $table->decimal('opening_balance',18,2)->default(0); $table->decimal('closing_balance',18,2)->default(0); $table->decimal('matched_amount',18,2)->default(0); $table->decimal('unmatched_amount',18,2)->default(0); $table->decimal('difference_amount',18,2)->default(0);
            $table->string('status',20)->default('DRAFT'); $table->ulid('started_by')->nullable(); $table->timestamp('started_at')->nullable(); $table->timestamp('completed_at')->nullable(); $table->timestamps();
            $table->unique(['organization_id','session_number']); $table->index(['organization_id','status']);
        });
        Schema::create('reconciliation_adjustments', function (Blueprint $table) {
            $table->ulid('id')->primary(); $table->ulid('organization_id'); $table->ulid('reconciliation_session_id'); $table->ulid('bank_transaction_id')->nullable();
            $table->string('adjustment_type',30); $table->decimal('amount',18,2); $table->text('reason'); $table->string('reference')->nullable(); $table->string('status',20)->default('PENDING'); $table->ulid('created_by')->nullable(); $table->ulid('approved_by')->nullable(); $table->timestamps();
        });
    }
    public function down(): void { foreach (['reconciliation_adjustments','reconciliation_sessions','reconciliation_matches','reconciliation_transactions','bank_transactions','bank_statements','bank_accounts'] as $table) Schema::dropIfExists($table); }
};
