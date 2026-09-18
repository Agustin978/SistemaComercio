<?php

namespace App\Inventory\Livewire;

use App\Catalog\Models\Product;
use App\Inventory\Enums\StockMovementType;
use App\Inventory\Models\StockMovement;
use App\Inventory\Services\InventoryService;
use App\Shared\Exceptions\DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Ajuste de stock')]
final class StockAdjustment extends Component
{
    use AuthorizesRequests;

    public Product $product;

    public string $type = 'purchase';

    public string $quantity = '';

    public string $reason = '';

    public ?string $status = null;

    public function mount(Product $product): void
    {
        $this->authorize('viewAny', StockMovement::class);
        $this->product = $product;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(StockMovementType::class)],
            'quantity' => ['required', 'integer', 'not_in:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function adjust(InventoryService $service): void
    {
        $this->authorize('create', StockMovement::class);
        $this->validate();
        $this->status = null;

        try {
            $movement = $service->adjust(
                $this->product,
                (int) $this->quantity,
                StockMovementType::from($this->type),
                $this->reason === '' ? null : $this->reason,
                auth()->user(),
            );
        } catch (DomainException $exception) {
            $this->addError('quantity', $exception->getMessage());

            return;
        }

        $this->reset('quantity', 'reason');
        $this->status = "Movimiento registrado: stock actual {$movement->stock_after}.";
    }

    public function render(): View
    {
        return view('inventory.stock-adjustment', [
            'movements' => StockMovement::query()
                ->with('user')
                ->where('product_id', $this->product->id)
                ->orderByDesc('id')
                ->limit(20)
                ->get(),
            'types' => StockMovementType::cases(),
        ]);
    }
}
