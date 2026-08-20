<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** PRD 02 §17 dan §20 — amil dan amil assignment. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amils', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id');

            // PRD 02 §18 — amil boleh belum punya user account.
            $table->ulid('user_id')->nullable();

            $table->string('business_number')->unique();
            $table->string('name');
            $table->string('employee_number')->nullable();

            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();

            $table->string('status', 20)->default('active');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['organization_id', 'status']);
        });

        // PRD 02 §37.3 — satu user hanya boleh punya satu amil aktif per organisasi.
        DB::statement("CREATE UNIQUE INDEX amils_active_user_unique ON amils (organization_id, user_id) WHERE user_id IS NOT NULL AND status = 'active' AND deleted_at IS NULL");
        DB::statement('CREATE UNIQUE INDEX amils_employee_number_unique ON amils (organization_id, employee_number) WHERE employee_number IS NOT NULL AND deleted_at IS NULL');

        Schema::create('amil_assignments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('amil_id');
            $table->ulid('organization_id');

            $table->string('assignment_type', 50);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->foreign('amil_id')->references('id')->on('amils')->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->index(['amil_id', 'status']);
        });

        // Satu jenis assignment aktif per amil.
        DB::statement("CREATE UNIQUE INDEX amil_assignments_active_unique ON amil_assignments (amil_id, assignment_type) WHERE status = 'active'");
    }

    public function down(): void
    {
        Schema::dropIfExists('amil_assignments');
        Schema::dropIfExists('amils');
    }
};
