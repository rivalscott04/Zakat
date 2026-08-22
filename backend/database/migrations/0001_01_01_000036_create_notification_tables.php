<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** PRD 16C, 16E, 16H, 16I, 16J, 16L, 16R, dan 16S. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('template_code');
            $table->string('name');
            $table->string('channel');
            $table->string('subject')->nullable();
            $table->text('content');
            $table->string('locale', 10)->default('id');
            $table->string('status')->default('draft');
            // PRD 16K §26 — variabel yang dikenal template, dipakai saat validasi.
            $table->jsonb('variables')->nullable();
            $table->timestamps();

            // PRD 16J §23 — unik dalam organization.
            $table->unique(['organization_id', 'template_code']);
        });

        Schema::create('notification_rules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('event_name');
            $table->foreignUlid('template_id')->nullable()->constrained('notification_templates')->nullOnDelete();
            $table->jsonb('channels');
            $table->string('recipient_strategy');
            $table->jsonb('recipient_config')->nullable();
            $table->string('priority')->default('normal');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['organization_id', 'event_name', 'enabled']);
        });

        Schema::create('notification_batches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('batch_number')->unique();
            $table->string('name');
            $table->unsignedInteger('total_recipient')->default(0);
            $table->unsignedInteger('total_success')->default(0);
            $table->unsignedInteger('total_failed')->default(0);
            $table->string('status')->default('draft');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('notification_number')->unique();
            $table->string('event_name')->nullable();
            $table->foreignUlid('rule_id')->nullable()->constrained('notification_rules')->nullOnDelete();
            $table->foreignUlid('template_id')->nullable()->constrained('notification_templates')->nullOnDelete();
            $table->foreignUlid('batch_id')->nullable()->constrained('notification_batches')->cascadeOnDelete();
            $table->string('recipient_type');
            $table->string('recipient_id');
            $table->string('title');
            $table->text('message');
            $table->jsonb('data')->nullable();
            $table->string('priority')->default('normal');
            $table->string('status')->default('draft');
            // PRD 16Y §7 — event yang sama tidak boleh menghasilkan notification ganda.
            $table->string('idempotency_key')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'recipient_type', 'recipient_id', 'read_at']);
            $table->index(['status', 'scheduled_at']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX notifications_idempotency_unique
             ON notifications (organization_id, recipient_type, recipient_id, idempotency_key)
             WHERE idempotency_key IS NOT NULL'
        );

        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('notification_id')->constrained()->cascadeOnDelete();
            $table->string('channel');
            $table->string('recipient_address')->nullable();
            $table->string('status')->default('pending');
            $table->string('provider')->nullable();
            $table->string('provider_reference')->nullable();
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->unsignedSmallInteger('max_attempts')->default(3);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['notification_id', 'channel']);
            $table->index(['status', 'channel']);
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('event_name');
            $table->string('channel');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'organization_id', 'event_name', 'channel'], 'notification_preferences_unique');
        });

        Schema::create('notification_webhooks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('url');
            // PRD 16Y §16 — secret wajib terenkripsi.
            $table->text('secret_encrypted')->nullable();
            $table->jsonb('events')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('notification_email_configs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('driver')->default('smtp');
            $table->string('host')->nullable();
            $table->unsignedSmallInteger('port')->nullable();
            // PRD 16H §18 — credential terenkripsi dan tidak pernah ditampilkan penuh.
            $table->text('username_encrypted')->nullable();
            $table->text('password_encrypted')->nullable();
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('encryption')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            // Satu konfigurasi email per organisasi.
            $table->unique('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_email_configs');
        Schema::dropIfExists('notification_webhooks');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('notification_batches');
        Schema::dropIfExists('notification_rules');
        Schema::dropIfExists('notification_templates');
    }
};
