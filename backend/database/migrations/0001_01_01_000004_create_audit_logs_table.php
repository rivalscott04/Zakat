<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** PRD 00 §24 dan PRD 02 §40 — audit trail. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('request_id')->nullable();

            $table->ulid('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->ulid('organization_id')->nullable();

            $table->string('action');
            $table->string('entity_type')->nullable();
            $table->ulid('entity_id')->nullable();

            $table->jsonb('before')->nullable();
            $table->jsonb('after')->nullable();
            $table->jsonb('context')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('created_at');

            // Audit tidak boleh hilang saat user dihapus, jadi tanpa cascade delete.
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['organization_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
