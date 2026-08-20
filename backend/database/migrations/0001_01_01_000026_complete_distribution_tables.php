<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Melengkapi PRD 12: reservation, realisasi, jadwal, batch, bukti, dan konfirmasi. */
return new class extends Migration
{
    public function up(): void
    {
        // PRD 12I §23, 12T, 12U, 12V — jejak aktor dan alasan tiap transisi status.
        Schema::table('distributions', function (Blueprint $t) {
            $t->ulid('submitted_by')->nullable();
            $t->dateTime('submitted_at')->nullable();
            $t->ulid('approved_by')->nullable();
            $t->dateTime('approved_at')->nullable();
            $t->text('rejection_reason')->nullable();
            $t->ulid('cancelled_by')->nullable();
            $t->dateTime('cancelled_at')->nullable();
            $t->text('cancellation_reason')->nullable();
            $t->ulid('reversed_by')->nullable();
            $t->dateTime('reversed_at')->nullable();
            $t->text('reversal_reason')->nullable();
            $t->string('failure_reason', 40)->nullable();
            $t->text('failure_note')->nullable();
            $t->dateTime('failed_at')->nullable();
            $t->unsignedSmallInteger('retry_count')->default(0);
            $t->ulid('batch_id')->nullable();
            $t->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $t->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();
            $t->foreign('reversed_by')->references('id')->on('users')->nullOnDelete();
        });

        // PRD 12F — hasil review request dan tautan ke distribution yang dibuat.
        Schema::table('distribution_requests', function (Blueprint $t) {
            $t->ulid('reviewed_by')->nullable();
            $t->dateTime('reviewed_at')->nullable();
            $t->text('rejection_reason')->nullable();
            $t->ulid('distribution_id')->nullable();
            $t->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            $t->foreign('distribution_id')->references('id')->on('distributions')->nullOnDelete();
        });

        // PRD 12H §20. fund_reservation_id menautkan ke reservation milik modul Fund
        // yang tetap menjadi otoritas saldo; baris ini adalah proyeksi sisi Distribution.
        Schema::create('distribution_reservations', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('distribution_id');
            $t->ulid('fund_id');
            $t->ulid('fund_reservation_id')->nullable();
            $t->decimal('reserved_amount', 20, 2);
            $t->string('currency', 3)->default('IDR');
            $t->dateTime('reserved_at');
            $t->dateTime('released_at')->nullable();
            $t->string('status', 20)->default('active');
            $t->timestamps();
            $t->foreign('distribution_id')->references('id')->on('distributions')->cascadeOnDelete();
            $t->foreign('fund_id')->references('id')->on('funds')->restrictOnDelete();
            $t->foreign('fund_reservation_id')->references('id')->on('fund_reservations')->nullOnDelete();
            $t->index(['distribution_id', 'status']);
        });

        // Satu distribution hanya boleh punya satu reservation aktif (PRD 12H §19).
        DB::statement("CREATE UNIQUE INDEX distribution_reservations_active_unique ON distribution_reservations (distribution_id) WHERE status = 'active'");

        // PRD 12L §29.
        Schema::create('distribution_cash_details', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('distribution_id');
            $t->decimal('amount', 20, 2);
            $t->string('currency', 3)->default('IDR');
            $t->ulid('cashier_id')->nullable();
            $t->dateTime('disbursed_at');
            $t->string('receipt_number', 60)->nullable();
            $t->timestamps();
            $t->foreign('distribution_id')->references('id')->on('distributions')->cascadeOnDelete();
            $t->foreign('cashier_id')->references('id')->on('users')->nullOnDelete();
        });

        // PRD 12M §31. Nomor rekening disimpan terenkripsi dan hanya versi mask yang
        // boleh keluar tanpa permission khusus (PRD 12D §9).
        Schema::create('distribution_bank_transfers', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('distribution_id');
            $t->string('bank_name', 100);
            $t->string('account_holder_name', 150);
            $t->text('account_number_encrypted');
            $t->string('account_number_masked', 40);
            $t->string('transfer_reference', 100)->nullable();
            $t->decimal('transfer_amount', 20, 2);
            $t->date('transfer_date')->nullable();
            $t->string('status', 20)->default('pending');
            $t->text('failure_reason')->nullable();
            $t->timestamps();
            $t->foreign('distribution_id')->references('id')->on('distributions')->cascadeOnDelete();
            $t->index(['distribution_id', 'status']);
        });

        // PRD 12N §34.
        Schema::create('distribution_schedules', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('distribution_id');
            $t->string('schedule_type', 20)->default('one_time');
            $t->date('scheduled_date');
            $t->decimal('amount', 20, 2);
            $t->string('status', 20)->default('pending');
            $t->dateTime('processed_at')->nullable();
            $t->timestamps();
            $t->foreign('distribution_id')->references('id')->on('distributions')->cascadeOnDelete();
            $t->index(['distribution_id', 'status']);
        });

        // PRD 12P §39.
        Schema::create('distribution_batches', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('organization_id');
            $t->string('batch_number', 30)->unique();
            $t->string('name');
            $t->ulid('program_id')->nullable();
            $t->ulid('fund_id');
            $t->string('distribution_type', 30);
            $t->decimal('total_amount', 20, 2)->default(0);
            $t->unsignedInteger('total_beneficiary')->default(0);
            $t->string('status', 20)->default('draft');
            $t->ulid('created_by')->nullable();
            $t->ulid('approved_by')->nullable();
            $t->dateTime('approved_at')->nullable();
            $t->timestamps();
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $t->foreign('program_id')->references('id')->on('programs')->nullOnDelete();
            $t->foreign('fund_id')->references('id')->on('funds')->restrictOnDelete();
            $t->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $t->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $t->index(['organization_id', 'status']);
        });

        Schema::table('distributions', function (Blueprint $t) {
            $t->foreign('batch_id')->references('id')->on('distribution_batches')->nullOnDelete();
        });

        // PRD 12Q §42. batch_id ditambahkan karena alur PRD 12P §41 menambahkan
        // beneficiary sebelum distribution per penerima dibuat saat proses batch.
        Schema::create('distribution_beneficiaries', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('batch_id');
            $t->ulid('distribution_id')->nullable();
            $t->ulid('mustahik_id');
            $t->decimal('approved_amount', 20, 2);
            $t->decimal('distributed_amount', 20, 2)->default(0);
            $t->string('status', 20)->default('pending');
            $t->string('failure_reason', 40)->nullable();
            $t->text('failure_note')->nullable();
            $t->timestamps();
            $t->foreign('batch_id')->references('id')->on('distribution_batches')->cascadeOnDelete();
            $t->foreign('distribution_id')->references('id')->on('distributions')->nullOnDelete();
            $t->foreign('mustahik_id')->references('id')->on('mustahiks')->restrictOnDelete();
            $t->unique(['batch_id', 'mustahik_id']);
        });

        // PRD 12R §44. file_id mengacu ke modul Document Management (PRD 15) yang
        // belum diimplementasikan, jadi belum ada foreign key.
        Schema::create('distribution_proofs', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('distribution_id');
            $t->string('proof_type', 30);
            $t->ulid('file_id')->nullable();
            $t->string('reference_number', 100)->nullable();
            $t->text('note')->nullable();
            $t->ulid('uploaded_by')->nullable();
            $t->ulid('verified_by')->nullable();
            $t->dateTime('verified_at')->nullable();
            $t->timestamps();
            $t->foreign('distribution_id')->references('id')->on('distributions')->cascadeOnDelete();
            $t->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
            $t->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            $t->index(['distribution_id', 'proof_type']);
        });

        // PRD 12S §46.
        Schema::create('distribution_confirmations', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('distribution_id');
            $t->string('confirmation_method', 20);
            $t->dateTime('confirmed_at');
            $t->ulid('confirmed_by')->nullable();
            $t->jsonb('confirmation_data')->nullable();
            $t->string('status', 20)->default('confirmed');
            $t->timestamps();
            $t->foreign('distribution_id')->references('id')->on('distributions')->cascadeOnDelete();
            $t->foreign('confirmed_by')->references('id')->on('users')->nullOnDelete();
        });

        // Satu konfirmasi penerimaan yang berlaku per distribution.
        DB::statement("CREATE UNIQUE INDEX distribution_confirmations_active_unique ON distribution_confirmations (distribution_id) WHERE status = 'confirmed'");
    }

    public function down(): void
    {
        Schema::table('distributions', function (Blueprint $t) {
            $t->dropForeign(['batch_id']);
        });

        foreach ([
            'distribution_confirmations',
            'distribution_proofs',
            'distribution_beneficiaries',
            'distribution_batches',
            'distribution_schedules',
            'distribution_bank_transfers',
            'distribution_cash_details',
            'distribution_reservations',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('distribution_requests', function (Blueprint $t) {
            $t->dropConstrainedForeignKey('reviewed_by');
            $t->dropConstrainedForeignKey('distribution_id');
            $t->dropColumn(['reviewed_at', 'rejection_reason']);
        });

        Schema::table('distributions', function (Blueprint $t) {
            $t->dropConstrainedForeignKey('approved_by');
            $t->dropConstrainedForeignKey('cancelled_by');
            $t->dropConstrainedForeignKey('reversed_by');
            $t->dropColumn([
                'submitted_by', 'submitted_at', 'approved_at', 'rejection_reason',
                'cancelled_at', 'cancellation_reason', 'reversed_at', 'reversal_reason',
                'failure_reason', 'failure_note', 'failed_at', 'retry_count', 'batch_id',
            ]);
        });
    }
};
