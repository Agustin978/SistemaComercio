<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">Stock de «{{ $product->name }}»</flux:heading>
        <flux:button href="{{ route('admin.products.index') }}">Volver a productos</flux:button>
    </div>

    <flux:text>SKU {{ $product->sku }} · Stock actual: <strong>{{ $product->stock_on_hand }}</strong></flux:text>

    @if ($status)
        <flux:callout variant="success" icon="check-circle">
            <flux:callout.text>{{ $status }}</flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="adjust" class="grid max-w-3xl gap-4 sm:grid-cols-4">
        <flux:select wire:model="type" label="Tipo">
            @foreach ($types as $movementType)
                <flux:select.option value="{{ $movementType->value }}">{{ $movementType->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input wire:model="quantity" label="Cantidad (con signo)" type="number" step="1" required />
        <flux:input wire:model="reason" label="Motivo" />

        <div class="flex items-end">
            <flux:button type="submit" variant="primary">Registrar</flux:button>
        </div>
    </form>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Fecha</flux:table.column>
            <flux:table.column>Tipo</flux:table.column>
            <flux:table.column>Cantidad</flux:table.column>
            <flux:table.column>Stock luego</flux:table.column>
            <flux:table.column>Motivo</flux:table.column>
            <flux:table.column>Usuario</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($movements as $movement)
                <flux:table.row :key="$movement->id">
                    <flux:table.cell>{{ $movement->created_at?->format('Y-m-d H:i') }}</flux:table.cell>
                    <flux:table.cell>{{ $movement->type->label() }}</flux:table.cell>
                    <flux:table.cell>{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</flux:table.cell>
                    <flux:table.cell>{{ $movement->stock_after }}</flux:table.cell>
                    <flux:table.cell>{{ $movement->reason ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $movement->user?->name ?? 'sistema' }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">Sin movimientos todavía.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
