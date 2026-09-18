<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('type', 32);
            $table->integer('quantity');
            $table->integer('stock_after');
            $table->string('reason', 255)->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index('type');
        });

        DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_quantity_non_zero CHECK (quantity <> 0)');
        DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_stock_after_non_negative CHECK (stock_after >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
