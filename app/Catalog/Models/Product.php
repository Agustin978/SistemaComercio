<?php

namespace App\Catalog\Models;

use App\Shared\Support\Money;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Los decimales llegan como cadenas por el cast decimal:2; Larastan los inferiría como float desde la migración.
 *
 * @property string $price
 * @property string $cost
 * @property string $tax_rate
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    use SoftDeletes;

    // uuid y stock_on_hand quedan fuera a propósito: el uuid lo genera el modelo al crear y el stock solo lo escribe InventoryService.
    protected $fillable = ['category_id', 'sku', 'slug', 'name', 'description', 'price', 'cost', 'tax_rate', 'is_active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            if (! array_key_exists('uuid', $product->getAttributes())) {
                $product->uuid = (string) Str::uuid7();
            }
        });
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function netPrice(): string
    {
        return Money::splitTax($this->price, $this->tax_rate)['net'];
    }

    public function taxAmount(): string
    {
        return Money::splitTax($this->price, $this->tax_rate)['tax'];
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
