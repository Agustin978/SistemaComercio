<?php

namespace App\Catalog\Livewire;

use App\Catalog\Data\ProductData;
use App\Catalog\Models\Category;
use App\Catalog\Models\Product;
use App\Catalog\Services\ProductService;
use App\Shared\Exceptions\DomainException;
use App\Shared\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Producto')]
final class ProductForm extends Component
{
    use AuthorizesRequests;

    public ?Product $product = null;

    public string $sku = '';

    public string $slug = '';

    public string $name = '';

    public string $description = '';

    public string $price = '';

    public string $cost = '0.00';

    public string $taxRate = '21.00';

    public ?int $categoryId = null;

    public bool $isActive = true;

    // Sin parámetro de ruta, el contenedor inyecta un modelo vacío (exists = false): eso es "crear".
    public function mount(Product $product): void
    {
        $this->product = $product->exists ? $product : null;

        if ($this->product !== null) {
            $this->authorize('update', $product);
            $this->sku = $product->sku;
            $this->slug = $product->slug;
            $this->name = $product->name;
            $this->description = $product->description ?? '';
            $this->price = $product->price;
            $this->cost = $product->cost;
            $this->taxRate = $product->tax_rate;
            $this->categoryId = $product->category_id;
            $this->isActive = $product->is_active;
        } else {
            $this->authorize('create', Product::class);
        }
    }

    public function updatedName(): void
    {
        if ($this->product === null || $this->slug === '') {
            $this->slug = Str::slug($this->name);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $decimal = ['required', 'string', 'regex:/^\d{1,10}(\.\d{1,2})?$/'];

        return [
            'sku' => ['required', 'string', 'max:64', Rule::unique('products', 'sku')->ignore($this->product?->id)],
            'slug' => ['required', 'string', 'max:160', 'alpha_dash', Rule::unique('products', 'slug')->ignore($this->product?->id)],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
            'price' => $decimal,
            'cost' => $decimal,
            'taxRate' => ['required', 'string', 'regex:/^\d{1,3}(\.\d{1,2})?$/', 'numeric', 'max:100'],
            'categoryId' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'isActive' => ['boolean'],
        ];
    }

    /**
     * Desglose neto/IVA del precio tal como se está escribiendo; null hasta que ambos valores sean válidos.
     *
     * @return array{net: string, tax: string}|null
     */
    public function breakdown(): ?array
    {
        if (preg_match('/^\d{1,10}(\.\d{1,2})?$/', $this->price) !== 1 || preg_match('/^\d{1,3}(\.\d{1,2})?$/', $this->taxRate) !== 1) {
            return null;
        }

        return Money::splitTax($this->price, $this->taxRate);
    }

    public function save(ProductService $service): void
    {
        $this->validate();

        $data = new ProductData(
            sku: $this->sku,
            slug: $this->slug,
            name: $this->name,
            price: Money::round($this->price),
            cost: Money::round($this->cost),
            taxRate: Money::round($this->taxRate),
            description: $this->description === '' ? null : $this->description,
            categoryId: $this->categoryId,
            isActive: $this->isActive,
        );

        try {
            $product = $this->product === null
                ? $service->create($data)
                : $service->update($this->product, $data);
        } catch (DomainException $exception) {
            $this->addError('sku', $exception->getMessage());

            return;
        }

        session()->flash('status', "Producto «{$product->name}» guardado.");
        $this->redirectRoute('admin.products.index');
    }

    public function render(): View
    {
        return view('catalog.product-form', [
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'breakdown' => $this->breakdown(),
        ]);
    }
}
