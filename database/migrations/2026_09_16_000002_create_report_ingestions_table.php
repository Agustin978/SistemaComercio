<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_ingestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reported_system_id')->constrained()->restrictOnDelete();
            $table->string('report_type');
            $table->string('idempotency_key');
            $table->string('payload_hash', 64);
            $table->jsonb('payload');
            $table->timestamps();

            $table->unique(['reported_system_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_ingestions');
    }
};
