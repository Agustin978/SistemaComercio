<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_ingestion_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('reported_system_id')->constrained()->restrictOnDelete();
            $table->string('origin');
            $table->string('level');
            $table->text('message');
            $table->jsonb('context')->nullable();
            $table->timestampTz('logged_at');
            $table->timestamps();

            $table->index(['reported_system_id', 'origin', 'level', 'logged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
