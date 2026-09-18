<?php

namespace App\Catalog\Livewire;

use App\Catalog\Models\Category;
use App\Catalog\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Panel del comerciante')]
final class MerchantDashboard extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('viewAny', Product::class);
    }

    public function render(): View
    {
        return view('admin.dashboard', [
            'productCount' => Product::query()->count(),
            'categoryCount' => Category::query()->count(),
            'outOfStockCount' => Product::query()->where('is_active', true)->where('stock_on_hand', 0)->count(),
        ]);
    }
}
