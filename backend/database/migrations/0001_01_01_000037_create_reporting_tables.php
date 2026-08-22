<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** PRD 19C, 19F, 19H, 19I, 19J, 19K, dan 19P. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // NULL berarti laporan bawaan sistem yang berlaku untuk semua
            // organisasi, mengikuti pola role sistem pada PRD 01 §24.
            $table->foreignUlid('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('report_number')->unique();
            $table->string('report_code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category');
            $table->string('report_type')->default('TABULAR');
            $table->string('data_source')->nullable();
            $table->string('visibility')->default('INTERNAL');
            $table->string('status')->default('active');
            $table->boolean('is_system')->default(false);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'category', 'status']);
        });

        // PRD 19C §7 — report code stabil dan unik dalam lingkupnya.
        DB::statement('CREATE UNIQUE INDEX reports_system_code_unique ON reports (report_code) WHERE organization_id IS NULL');
        DB::statement('CREATE UNIQUE INDEX reports_org_code_unique ON reports (organization_id, report_code) WHERE organization_id IS NOT NULL');

        Schema::create('report_parameters', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('report_id')->constrained()->cascadeOnDelete();
            $table->string('parameter_code');
            $table->string('label');
            $table->string('type');
            $table->boolean('required')->default(false);
            $table->string('default_value')->nullable();
            $table->string('options_source')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['report_id', 'parameter_code']);
        });

        Schema::create('report_runs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('run_number')->unique();
            $table->foreignUlid('report_id')->constrained()->cascadeOnDelete();
            $table->jsonb('parameters')->nullable();
            // PRD 19B §4 — snapshot tidak boleh berubah setelah completed.
            $table->jsonb('snapshot_data')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->string('status')->default('QUEUED');
            $table->foreignUlid('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'report_id', 'status']);
        });

        Schema::create('report_exports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('report_run_id')->constrained()->cascadeOnDelete();
            $table->string('format');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->default(0);
            // PRD 19I §28 — tautan sementara punya masa berlaku.
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['report_run_id', 'format']);
        });

        Schema::create('report_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('template_code');
            $table->string('name');
            $table->foreignUlid('report_id')->constrained()->cascadeOnDelete();
            $table->jsonb('configuration')->nullable();
            $table->string('status')->default('draft');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'template_code']);
        });

        Schema::create('report_schedules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('report_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('frequency');
            $table->jsonb('schedule_configuration')->nullable();
            $table->jsonb('parameters')->nullable();
            $table->string('output_format')->default('CSV');
            $table->jsonb('recipient_configuration')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'next_run_at']);
        });

        Schema::create('user_report_favorites', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('report_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['user_id', 'report_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_report_favorites');
        Schema::dropIfExists('report_schedules');
        Schema::dropIfExists('report_templates');
        Schema::dropIfExists('report_exports');
        Schema::dropIfExists('report_runs');
        Schema::dropIfExists('report_parameters');
        Schema::dropIfExists('reports');
    }
};
