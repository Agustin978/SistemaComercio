<?php

namespace App\Catalog\Livewire;

use App\Catalog\Data\CategoryData;
use App\Catalog\Models\Category;
use App\Catalog\Services\CategoryService;
use App\Shared\Exceptions\DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Categoría')]
final class CategoryForm extends Component
{
    use AuthorizesRequests;

    public ?Category $category = null;

    public string $name = '';

    public string $slug = '';

    public ?int $parentId = null;

    // Sin parámetro de ruta, el contenedor inyecta un modelo vacío (exists = false): eso es "crear".
    public function mount(Category $category): void
    {
        $this->category = $category->exists ? $category : null;

        if ($this->category !== null) {
            $this->authorize('update', $category);
            $this->name = $category->name;
            $this->slug = $category->slug;
            $this->parentId = $category->parent_id;
        } else {
            $this->authorize('create', Category::class);
        }
    }

    public function updatedName(): void
    {
        if ($this->category === null || $this->slug === '') {
            $this->slug = Str::slug($this->name);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:140', 'alpha_dash', Rule::unique('categories', 'slug')->ignore($this->category?->id)],
            'parentId' => ['nullable', 'integer', Rule::exists('categories', 'id')],
        ];
    }

    public function save(CategoryService $service): void
    {
        $this->validate();

        $data = new CategoryData(name: $this->name, slug: $this->slug, parentId: $this->parentId);

        try {
            $category = $this->category === null
                ? $service->create($data)
                : $service->update($this->category, $data);
        } catch (DomainException $exception) {
            $this->addError('parentId', $exception->getMessage());

            return;
        }

        session()->flash('status', "Categoría «{$category->name}» guardada.");
        $this->redirectRoute('admin.categories.index');
    }

    public function render(): View
    {
        $parents = Category::query()
            ->when($this->category !== null, fn ($query) => $query->whereKeyNot($this->category?->id))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('catalog.category-form', ['parents' => $parents]);
    }
}
