<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** PRD 02 §5 — organization sebagai tenant utama. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('business_number')->unique();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('organization_type', 20);
            $table->string('status', 20)->default('draft');

            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('website')->nullable();

            // PRD 02 §5 — logo dikelola modul Document Management (PRD 15, belum ada).
            $table->ulid('logo_document_id')->nullable();

            $table->string('currency', 3)->default('IDR');
            $table->string('timezone', 64)->default('Asia/Makassar');
            $table->string('locale', 10)->default('id');

            $table->ulid('parent_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id']);
            $table->index(['status']);
        });

        // Self-referencing FK dipasang terpisah: primary key baru ada setelah
        // tabel selesai dibuat.
        Schema::table('organizations', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('organizations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
