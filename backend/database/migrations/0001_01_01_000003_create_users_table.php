<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** PRD 01 §5 — user entity, dan PRD 01 §18 — session metadata. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Home organization. Otoritas akses tetap organization_members (PRD 02 §13).
            // NULL untuk user platform-level seperti Super Admin.
            $table->ulid('organization_id')->nullable();

            $table->string('name');
            $table->string('email');
            $table->string('username')->nullable();
            $table->string('password');
            $table->string('phone', 32)->nullable();
            $table->string('avatar_path')->nullable();

            $table->string('status', 20)->default('pending');

            // PRD 01 §20 dan §21 — failed login protection dan account lock.
            $table->unsignedSmallInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();

            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
            $table->index(['organization_id', 'status']);
        });

        // Email unik hanya di antara user yang belum di-soft-delete (PRD 01 §34 pakai soft delete).
        DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (lower(email)) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX users_username_unique ON users (lower(username)) WHERE deleted_at IS NULL AND username IS NOT NULL');

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // PRD 01 §18 `user_sessions`. Memakai tabel sessions bawaan Laravel supaya
        // database session driver tetap jadi satu-satunya sumber kebenaran session.
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->ulid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
