<?php

namespace App\Catalog\Livewire;

use App\Catalog\Models\Category;
use App\Catalog\Services\CategoryService;
use App\Shared\Exceptions\DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Categorías')]
final class CategoryIndex extends Component
{
    use AuthorizesRequests;

    public ?int $reassigningId = null;

    public string $reassignTarget = '';

    public ?string $error = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Category::class);
    }

    public function delete(int $id, CategoryService $service): void
    {
        $category = Category::query()->findOrFail($id);
        $this->authorize('delete', $category);
        $this->error = null;

        try {
            $service->delete($category);
            session()->flash('status', "Categoría «{$category->name}» borrada.");
        } catch (DomainException $exception) {
            $this->error = $exception->getMessage();
            // Solo los productos borrados admiten reasignación en bloque; con activos no hay salida desde acá.
            $this->reassigningId = $this->hasActiveProducts($category) ? null : $category->id;
            $this->reassignTarget = '';
        }
    }

    public function confirmReassign(CategoryService $service): void
    {
        if ($this->reassigningId === null) {
            return;
        }

        $category = Category::query()->findOrFail($this->reassigningId);
        $this->authorize('delete', $category);
        $this->error = null;

        try {
            if ($this->reassignTarget === 'none') {
                $service->delete($category, reassignTrashedToNone: true);
            } else {
                $target = Category::query()->findOrFail((int) $this->reassignTarget);
                $service->delete($category, $target);
            }

            $this->reassigningId = null;
            session()->flash('status', "Categoría «{$category->name}» borrada; sus productos borrados fueron reasignados.");
        } catch (DomainException $exception) {
            $this->error = $exception->getMessage();
        }
    }

    public function cancelReassign(): void
    {
        $this->reassigningId = null;
        $this->error = null;
    }

    public function render(CategoryService $service): View
    {
        $categories = Category::query()->orderBy('name')->get();
        $tree = $this->flattenTree($categories);

        $targets = [];
        if ($this->reassigningId !== null) {
            $excluded = [$this->reassigningId, ...$service->descendantIds(Category::query()->findOrFail($this->reassigningId))];
            $targets = array_values(array_filter($tree, fn (array $row): bool => ! in_array($row['category']->id, $excluded, true)));
        }

        return view('catalog.category-index', [
            'tree' => $tree,
            'targets' => $targets,
        ]);
    }

    private function hasActiveProducts(Category $category): bool
    {
        return $category->products()->exists();
    }

    /**
     * Ordena las categorías como árbol (padres antes que hijos) con su profundidad, sin consultas extra.
     *
     * @param  Collection<int, Category>  $categories
     * @return list<array{category: Category, depth: int}>
     */
    private function flattenTree(Collection $categories): array
    {
        $byParent = $categories->groupBy(fn (Category $category): string => (string) ($category->parent_id ?? 'root'));
        $rows = [];
        $visited = [];

        $walk = function (string $parentKey, int $depth) use (&$walk, &$rows, &$visited, $byParent): void {
            foreach ($byParent->get($parentKey, new Collection) as $category) {
                if (isset($visited[$category->id])) {
                    continue;
                }

                $visited[$category->id] = true;
                $rows[] = ['category' => $category, 'depth' => $depth];
                $walk((string) $category->id, $depth + 1);
            }
        };

        $walk('root', 0);

        // Categorías cuyo padre no existe en el listado (dato corrupto) se muestran igual, al final.
        foreach ($categories as $category) {
            if (! isset($visited[$category->id])) {
                $rows[] = ['category' => $category, 'depth' => 0];
            }
        }

        return $rows;
    }
}
