<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** PRD 13 — Payment Gateway. */
return new class extends Migration
{
    public function up(): void
    {
        // PRD 13B §4. Kredensial disimpan terenkripsi dan tidak pernah dikembalikan
        // utuh lewat API (PRD 13T §40, PRD 13U §5).
        Schema::create('payment_providers', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('organization_id');
            $t->string('provider_code', 30);
            $t->string('name');
            $t->string('driver', 30);
            $t->string('status', 20)->default('inactive');
            $t->text('config_encrypted')->nullable();
            $t->text('webhook_secret_encrypted')->nullable();
            $t->boolean('sandbox_mode')->default(true);
            $t->timestamps();
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            // PRD 13B §5 — provider code unik dalam satu organisasi.
            $t->unique(['organization_id', 'provider_code']);
        });

        // PRD 13C §7.
        Schema::create('payments', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('organization_id');
            $t->string('payment_number', 30)->unique();
            $t->ulid('provider_id');
            $t->string('provider_reference', 120)->nullable();
            $t->string('internal_reference', 120)->nullable();

            // PRD 13D §10 — sengaja tanpa foreign key supaya modul ini tidak
            // bergantung pada struktur internal modul sumber.
            $t->string('source_type', 20);
            $t->ulid('source_id');

            $t->string('payer_name')->nullable();
            $t->string('payer_email')->nullable();
            $t->string('payer_phone', 30)->nullable();

            $t->decimal('amount', 20, 2);
            $t->string('currency', 3)->default('IDR');
            $t->string('payment_method', 20)->nullable();
            $t->text('payment_url')->nullable();

            $t->dateTime('expires_at')->nullable();
            $t->dateTime('paid_at')->nullable();
            $t->string('status', 20)->default('created');
            $t->string('failure_reason', 30)->nullable();
            $t->text('failure_note')->nullable();

            // PRD 13K §22 — jejak verifikasi manual.
            $t->ulid('verified_by')->nullable();
            $t->dateTime('verified_at')->nullable();
            $t->text('verification_reason')->nullable();

            $t->ulid('cancelled_by')->nullable();
            $t->dateTime('cancelled_at')->nullable();
            $t->text('cancellation_reason')->nullable();

            $t->jsonb('metadata')->nullable();
            $t->ulid('created_by')->nullable();
            $t->timestamps();

            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $t->foreign('provider_id')->references('id')->on('payment_providers')->restrictOnDelete();
            $t->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            $t->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();
            $t->index(['organization_id', 'status']);
            $t->index(['source_type', 'source_id']);
        });

        // Referensi provider unik per provider, supaya satu transaksi di sisi
        // provider tidak pernah terpetakan ke dua payment.
        DB::statement('CREATE UNIQUE INDEX payments_provider_reference_unique ON payments (provider_id, provider_reference) WHERE provider_reference IS NOT NULL');

        // PRD 13I §18.
        Schema::create('payment_webhooks', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('organization_id')->nullable();
            $t->ulid('provider_id')->nullable();
            $t->ulid('payment_id')->nullable();
            $t->string('event_id', 120)->nullable();
            $t->string('event_type', 60)->nullable();
            $t->boolean('signature_valid')->default(false);
            $t->jsonb('payload')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->dateTime('received_at');
            $t->dateTime('processed_at')->nullable();
            $t->string('status', 20)->default('received');
            $t->text('error_message')->nullable();
            $t->timestamps();
            $t->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
            $t->foreign('provider_id')->references('id')->on('payment_providers')->nullOnDelete();
            $t->foreign('payment_id')->references('id')->on('payments')->nullOnDelete();
            $t->index(['provider_id', 'status']);
        });

        // PRD 13J §20 — kunci idempotency. Event yang sama dari provider yang sama
        // tidak boleh diproses dua kali.
        DB::statement('CREATE UNIQUE INDEX payment_webhooks_event_unique ON payment_webhooks (provider_id, event_id) WHERE event_id IS NOT NULL');

        // PRD 13O §27.
        Schema::create('payment_refunds', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('organization_id');
            $t->ulid('payment_id');
            $t->string('refund_number', 30)->unique();
            $t->decimal('amount', 20, 2);
            $t->text('reason');
            $t->string('status', 20)->default('requested');
            $t->text('rejection_reason')->nullable();
            $t->ulid('requested_by')->nullable();
            $t->ulid('approved_by')->nullable();
            $t->dateTime('requested_at');
            $t->dateTime('processed_at')->nullable();
            $t->timestamps();
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $t->foreign('payment_id')->references('id')->on('payments')->cascadeOnDelete();
            $t->foreign('requested_by')->references('id')->on('users')->nullOnDelete();
            $t->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $t->index(['payment_id', 'status']);
        });

        // PRD 13P §31.
        Schema::create('payment_reconciliations', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('organization_id');
            $t->ulid('payment_id');
            $t->string('provider_reference', 120)->nullable();
            $t->decimal('internal_amount', 20, 2);
            $t->decimal('provider_amount', 20, 2)->nullable();
            $t->string('internal_status', 20);
            $t->string('provider_status', 20)->nullable();
            $t->string('result', 20);
            $t->text('notes')->nullable();
            $t->dateTime('reconciled_at');
            $t->ulid('reconciled_by')->nullable();
            $t->timestamps();
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $t->foreign('payment_id')->references('id')->on('payments')->cascadeOnDelete();
            $t->foreign('reconciled_by')->references('id')->on('users')->nullOnDelete();
            $t->index(['payment_id', 'result']);
        });
    }

    public function down(): void
    {
        foreach (['payment_reconciliations', 'payment_refunds', 'payment_webhooks', 'payments', 'payment_providers'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
