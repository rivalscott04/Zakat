<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** PRD 18D §6 dan PRD 18O §23. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transparency_snapshots', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('snapshot_number')->unique();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('snapshot_type');
            // Agregat siap saji. Tidak pernah memuat identitas (PRD 18B §3).
            $table->jsonb('data')->nullable();
            $table->string('status')->default('DRAFT');
            $table->string('verification_status')->nullable();
            $table->jsonb('verification_notes')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->foreignUlid('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignUlid('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status', 'period_start']);
        });

        Schema::create('transparency_reports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('report_number')->unique();
            $table->string('title');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('report_type');
            $table->foreignUlid('snapshot_id')->nullable()->constrained('transparency_snapshots')->nullOnDelete();
            $table->foreignUlid('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->string('status')->default('DRAFT');
            $table->timestamp('published_at')->nullable();
            $table->foreignUlid('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transparency_reports');
        Schema::dropIfExists('transparency_snapshots');
    }
};
