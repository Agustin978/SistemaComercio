<?php

namespace App\Inventory\Models;

use App\Catalog\Models\Product;
use App\Inventory\Enums\StockMovementType;
use App\Shared\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = ['product_id', 'user_id', 'type', 'quantity', 'stock_after', 'reason'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['type' => StockMovementType::class];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
