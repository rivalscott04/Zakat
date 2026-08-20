<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funds', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id');
            $table->string('fund_code', 40)->unique();
            $table->string('name');
            $table->string('fund_type', 30);
            $table->string('category', 50)->nullable();
            $table->string('restriction_type', 30)->default('unrestricted');
            $table->string('status', 20)->default('active');
            $table->string('currency', 3)->default('IDR');
            $table->decimal('opening_balance', 20, 2)->default(0);
            $table->decimal('current_balance', 20, 2)->default(0);
            $table->decimal('available_balance', 20, 2)->default(0);
            $table->decimal('reserved_balance', 20, 2)->default(0);
            $table->decimal('allocated_balance', 20, 2)->default(0);
            $table->decimal('distributed_balance', 20, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->index(['organization_id', 'fund_type', 'status']);
        });
        Schema::create('fund_categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id');
            $table->string('fund_type', 30);
            $table->string('code', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'code']);
        });
        Schema::create('fund_movements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id');
            $table->ulid('fund_id');
            $table->string('movement_number', 30)->unique();
            $table->string('movement_type', 30);
            $table->string('direction', 5);
            $table->decimal('amount', 20, 2);
            $table->string('currency', 3)->default('IDR');
            $table->string('source_type', 40)->nullable();
            $table->ulid('source_id')->nullable();
            $table->string('reference_number', 80)->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('posted');
            $table->dateTime('effective_at');
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('fund_id')->references('id')->on('funds')->restrictOnDelete();
            $table->index(['organization_id', 'fund_id', 'effective_at']);
        });
        Schema::create('fund_allocations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id');
            $table->string('allocation_number', 30)->unique();
            $table->ulid('fund_id');
            $table->string('target_type', 30);
            $table->ulid('target_id')->nullable();
            $table->decimal('amount', 20, 2);
            $table->string('currency', 3)->default('IDR');
            $table->string('status', 25)->default('draft');
            $table->dateTime('allocated_at');
            $table->ulid('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->text('reason');
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('fund_id')->references('id')->on('funds')->restrictOnDelete();
        });
        Schema::create('fund_reservations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id');
            $table->string('reservation_number', 30)->unique();
            $table->ulid('fund_id');
            $table->string('target_type', 30);
            $table->ulid('target_id')->nullable();
            $table->decimal('amount', 20, 2);
            $table->string('currency', 3)->default('IDR');
            $table->string('status', 25)->default('active');
            $table->dateTime('reserved_at');
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('released_at')->nullable();
            $table->text('reason');
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('fund_id')->references('id')->on('funds')->restrictOnDelete();
        });
        Schema::create('fund_transfers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id');
            $table->string('transfer_number', 30)->unique();
            $table->ulid('source_fund_id');
            $table->ulid('destination_fund_id');
            $table->decimal('amount', 20, 2);
            $table->string('currency', 3)->default('IDR');
            $table->text('reason');
            $table->string('status', 20)->default('pending_approval');
            $table->ulid('requested_by')->nullable();
            $table->ulid('approved_by')->nullable();
            $table->dateTime('transferred_at')->nullable();
            $table->timestamps();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('source_fund_id')->references('id')->on('funds')->restrictOnDelete();
            $table->foreign('destination_fund_id')->references('id')->on('funds')->restrictOnDelete();
        });
        Schema::create('fund_adjustments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id');
            $table->string('adjustment_number', 30)->unique();
            $table->ulid('fund_id');
            $table->string('adjustment_type', 20);
            $table->decimal('amount', 20, 2);
            $table->string('currency', 3)->default('IDR');
            $table->text('reason');
            $table->string('reference')->nullable();
            $table->string('status', 20)->default('posted');
            $table->ulid('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('fund_id')->references('id')->on('funds')->restrictOnDelete();
        });
        Schema::create('fund_reconciliations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id');
            $table->string('reconciliation_number', 30)->unique();
            $table->ulid('fund_id');
            $table->date('reconciliation_date');
            $table->decimal('system_balance', 20, 2);
            $table->decimal('external_balance', 20, 2);
            $table->decimal('difference_amount', 20, 2);
            $table->string('status', 25);
            $table->text('notes')->nullable();
            $table->ulid('created_by')->nullable();
            $table->ulid('reviewed_by')->nullable();
            $table->timestamps();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('fund_id')->references('id')->on('funds')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        foreach (['fund_reconciliations', 'fund_adjustments', 'fund_transfers', 'fund_reservations', 'fund_allocations', 'fund_movements', 'fund_categories', 'funds'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
