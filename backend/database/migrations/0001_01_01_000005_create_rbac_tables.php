<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** PRD 01 §23 sampai §26 — role, permission, dan assignment. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Keputusan user 2026-08-20: format permission adalah `resource.action`
            // (PRD 01 §25). Kolom module hanya metadata untuk grouping menu,
            // tidak ikut membentuk string permission.
            $table->string('module', 50);
            $table->string('resource', 50);
            $table->string('action', 50);
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['resource', 'action']);
            $table->index('module');
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // NULL berarti role system-level yang berlaku lintas organisasi (PRD 01 §24).
            $table->ulid('organization_id')->nullable();

            $table->string('name');
            $table->string('code', 50);
            $table->string('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
        });

        // Role code unik per organisasi, dan unik global untuk role system.
        DB::statement('CREATE UNIQUE INDEX roles_org_code_unique ON roles (organization_id, code) WHERE organization_id IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX roles_system_code_unique ON roles (code) WHERE organization_id IS NULL');

        Schema::create('permission_role', function (Blueprint $table) {
            $table->ulid('role_id');
            $table->ulid('permission_id');

            $table->primary(['role_id', 'permission_id']);
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
        });

        // PRD 02 §14 — role user berlaku per organization context.
        Schema::create('role_user', function (Blueprint $table) {
            $table->ulid('user_id');
            $table->ulid('role_id');
            $table->ulid('organization_id')->nullable();
            $table->timestamps();

            $table->primary(['user_id', 'role_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->index(['user_id', 'organization_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }
};
