<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">Productos</flux:heading>
        <flux:button href="{{ route('admin.products.create') }}" variant="primary">Nuevo producto</flux:button>
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle">
            <flux:callout.text>{{ session('status') }}</flux:callout.text>
        </flux:callout>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:input wire:model.live.debounce.300ms="search" label="Buscar" placeholder="Nombre o SKU" />

        <flux:select wire:model.live="categoryId" label="Categoría">
            <flux:select.option value="">Todas</flux:select.option>
            @foreach ($categories as $category)
                <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="active" label="Estado">
            <flux:select.option value="">Todos</flux:select.option>
            <flux:select.option value="yes">Activos</flux:select.option>
            <flux:select.option value="no">Inactivos</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="stock" label="Stock">
            <flux:select.option value="">Todos</flux:select.option>
            <flux:select.option value="out">Sin stock</flux:select.option>
        </flux:select>
    </div>

    <flux:table :paginate="$products">
        <flux:table.columns>
            <flux:table.column>SKU</flux:table.column>
            <flux:table.column>Nombre</flux:table.column>
            <flux:table.column>Categoría</flux:table.column>
            <flux:table.column>Precio</flux:table.column>
            <flux:table.column>Stock</flux:table.column>
            <flux:table.column>Estado</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($products as $product)
                <flux:table.row :key="$product->id">
                    <flux:table.cell>{{ $product->sku }}</flux:table.cell>
                    <flux:table.cell>{{ $product->name }}</flux:table.cell>
                    <flux:table.cell>{{ $product->category?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell>$ {{ $product->price }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$product->stock_on_hand === 0 ? 'red' : 'zinc'">{{ $product->stock_on_hand }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$product->is_active ? 'green' : 'zinc'">{{ $product->is_active ? 'activo' : 'inactivo' }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="flex gap-2">
                        <flux:button size="sm" href="{{ route('admin.products.edit', $product) }}">Editar</flux:button>
                        <flux:button size="sm" href="{{ route('admin.stock.adjust', $product) }}">Stock</flux:button>
                        <flux:button size="sm" variant="danger" wire:click="delete({{ $product->id }})" wire:confirm="¿Borrar el producto «{{ $product->name }}»?">Borrar</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7">Sin productos para los filtros seleccionados.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
