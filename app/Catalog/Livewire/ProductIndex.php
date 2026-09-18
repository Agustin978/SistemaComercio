<?php

namespace App\Catalog\Livewire;

use App\Catalog\Models\Category;
use App\Catalog\Models\Product;
use App\Catalog\Services\ProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Productos')]
final class ProductIndex extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public ?int $categoryId = null;

    #[Url]
    public string $active = '';

    #[Url]
    public string $stock = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Product::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'categoryId', 'active', 'stock'], true)) {
            $this->resetPage();
        }
    }

    public function delete(int $id, ProductService $service): void
    {
        $product = Product::query()->findOrFail($id);
        $this->authorize('delete', $product);

        $service->delete($product);
        session()->flash('status', "Producto «{$product->name}» borrado.");
    }

    public function render(): View
    {
        $query = Product::query()->with('category');

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(fn ($q) => $q->where('name', 'ilike', $term)->orWhere('sku', 'ilike', $term));
        }

        if ($this->categoryId !== null) {
            $query->where('category_id', $this->categoryId);
        }

        if ($this->active === 'yes' || $this->active === 'no') {
            $query->where('is_active', $this->active === 'yes');
        }

        if ($this->stock === 'out') {
            $query->where('stock_on_hand', 0);
        }

        return view('catalog.product-index', [
            'products' => $query->orderBy('name')->paginate(25),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
