<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** PRD 00 §10 dan §11 — code registry dan business number sequence. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('code_registry', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 5)->unique();
            $table->string('name');
            $table->string('entity_type');
            $table->string('module');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('business_number_sequences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 5);
            $table->unsignedSmallInteger('year');
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();

            // PRD 00 §11 — satu counter per kombinasi code+tahun.
            $table->unique(['code', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_number_sequences');
        Schema::dropIfExists('code_registry');
    }
};
