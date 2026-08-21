<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->ulid('id')->primary(); $table->ulid('organization_id'); $table->string('document_number',30); $table->string('document_name'); $table->string('original_filename'); $table->string('stored_filename'); $table->string('document_type',30); $table->string('category',50)->nullable(); $table->string('mime_type',120); $table->string('extension',12); $table->unsignedBigInteger('file_size'); $table->string('storage_disk',30); $table->string('storage_path'); $table->string('checksum',64); $table->unsignedInteger('version')->default(1); $table->string('visibility',20)->default('PRIVATE'); $table->string('status',30)->default('ACTIVE'); $table->date('expires_at')->nullable(); $table->ulid('uploaded_by')->nullable(); $table->timestamps(); $table->softDeletes();
            $table->unique(['organization_id','document_number']); $table->index(['organization_id','checksum']); $table->index(['organization_id','status','document_type']);
        });
        Schema::create('document_relations', function (Blueprint $table) { $table->ulid('id')->primary(); $table->ulid('organization_id'); $table->ulid('document_id'); $table->string('entity_type',40); $table->ulid('entity_id'); $table->string('relation_type',30)->default('ATTACHMENT'); $table->ulid('created_by')->nullable(); $table->timestamp('created_at'); $table->index(['organization_id','entity_type','entity_id']); });
        Schema::create('document_versions', function (Blueprint $table) { $table->ulid('id')->primary(); $table->ulid('organization_id'); $table->ulid('document_id'); $table->unsignedInteger('version_number'); $table->string('storage_path'); $table->unsignedBigInteger('file_size'); $table->string('checksum',64); $table->text('change_note')->nullable(); $table->ulid('created_by')->nullable(); $table->timestamp('created_at'); $table->unique(['document_id','version_number']); });
        Schema::create('document_verifications', function (Blueprint $table) { $table->ulid('id')->primary(); $table->ulid('organization_id'); $table->ulid('document_id'); $table->string('status',20)->default('PENDING'); $table->text('verification_note')->nullable(); $table->ulid('verified_by')->nullable(); $table->timestamp('verified_at')->nullable(); $table->timestamps(); });
        Schema::create('document_access_logs', function (Blueprint $table) { $table->ulid('id')->primary(); $table->ulid('organization_id'); $table->ulid('document_id'); $table->ulid('user_id')->nullable(); $table->string('action',20); $table->string('ip_address',45)->nullable(); $table->text('user_agent')->nullable(); $table->timestamp('accessed_at'); $table->timestamp('created_at')->nullable(); $table->index(['organization_id','document_id','accessed_at']); });
    }
    public function down(): void { foreach (['document_access_logs','document_verifications','document_versions','document_relations','documents'] as $table) Schema::dropIfExists($table); }
};
