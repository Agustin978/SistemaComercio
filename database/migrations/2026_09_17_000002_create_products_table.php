<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('category_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('sku', 64)->unique();
            $table->string('slug', 160)->unique();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('cost', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(21);
            $table->integer('stock_on_hand')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id');
            $table->index('is_active');
        });

        // Invariantes numéricas en la base: el Service produce el error legible, la base garantiza.
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_stock_on_hand_non_negative CHECK (stock_on_hand >= 0)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_price_non_negative CHECK (price >= 0)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_cost_non_negative CHECK (cost >= 0)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_tax_rate_range CHECK (tax_rate >= 0 AND tax_rate <= 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
