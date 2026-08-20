<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id');
            $table->string('collection_number', 30)->unique();
            $table->ulid('muzaki_id');
            $table->ulid('calculation_id')->nullable();
            $table->ulid('zakat_type_id');
            $table->ulid('zakat_rule_id')->nullable();
            $table->date('collection_date');
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('currency', 3)->default('IDR');
            $table->decimal('expected_amount', 20, 8);
            $table->decimal('paid_amount', 20, 8)->default(0);
            $table->decimal('remaining_amount', 20, 8);
            $table->unsignedInteger('payment_count')->default(0);
            $table->string('source', 20)->default('manual');
            $table->string('overpayment_status', 20)->default('none');
            $table->jsonb('source_snapshot')->nullable();
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('muzaki_id')->references('id')->on('muzakis')->restrictOnDelete();
            $table->foreign('calculation_id')->references('id')->on('zakat_calculations')->restrictOnDelete();
            $table->foreign('zakat_type_id')->references('id')->on('zakat_types')->restrictOnDelete();
            $table->foreign('zakat_rule_id')->references('id')->on('zakat_rules')->nullOnDelete();
            $table->index(['organization_id', 'status', 'collection_date']);
        });
        Schema::create('collection_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('collection_id');
            $table->ulid('zakat_type_id');
            $table->ulid('calculation_id')->nullable();
            $table->string('description');
            $table->decimal('quantity', 20, 8)->nullable();
            $table->string('unit', 20)->nullable();
            $table->decimal('expected_amount', 20, 8);
            $table->decimal('paid_amount', 20, 8)->default(0);
            $table->decimal('remaining_amount', 20, 8);
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->foreign('collection_id')->references('id')->on('collections')->cascadeOnDelete();
            $table->foreign('zakat_type_id')->references('id')->on('zakat_types')->restrictOnDelete();
            $table->foreign('calculation_id')->references('id')->on('zakat_calculations')->nullOnDelete();
        });
        Schema::create('collection_payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id');
            $table->ulid('collection_id');
            $table->string('payment_reference', 80)->unique();
            $table->string('status', 20)->default('pending');
            $table->string('payment_method', 30);
            $table->string('payment_instrument', 60)->nullable();
            $table->decimal('amount', 20, 8);
            $table->string('currency', 3)->default('IDR');
            $table->dateTime('payment_date');
            $table->dateTime('verified_at')->nullable();
            $table->ulid('verified_by')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('collection_id')->references('id')->on('collections')->cascadeOnDelete();
            $table->index(['organization_id', 'status', 'payment_date']);
        });
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('payment_id');
            $table->ulid('collection_id');
            $table->ulid('collection_item_id')->nullable();
            $table->decimal('allocated_amount', 20, 8);
            $table->string('currency', 3)->default('IDR');
            $table->timestamps();
            $table->foreign('payment_id')->references('id')->on('collection_payments')->cascadeOnDelete();
            $table->foreign('collection_id')->references('id')->on('collections')->cascadeOnDelete();
            $table->foreign('collection_item_id')->references('id')->on('collection_items')->nullOnDelete();
            $table->unique(['payment_id', 'collection_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('collection_payments');
        Schema::dropIfExists('collection_items');
        Schema::dropIfExists('collections');
    }
};
