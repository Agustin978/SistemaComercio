<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_ingestion_divergences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_ingestion_id')->constrained()->cascadeOnDelete();
            $table->string('payload_hash', 64);
            $table->jsonb('payload');
            $table->timestamps();

            // Nombre explícito: el generado automáticamente supera los 63 caracteres que admite PostgreSQL.
            $table->unique(['report_ingestion_id', 'payload_hash'], 'report_ingestion_divergences_ingestion_hash_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_ingestion_divergences');
    }
};
