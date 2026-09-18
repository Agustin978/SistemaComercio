<div class="flex flex-col gap-6">
    <flux:heading size="xl">{{ $product === null ? 'Nuevo producto' : "Editar «{$product->name}»" }}</flux:heading>

    @if ($product !== null)
        <flux:text>Identificador para reportes (uuid): <code>{{ $product->uuid }}</code>. Stock actual: {{ $product->stock_on_hand }} — se ajusta desde <flux:link href="{{ route('admin.stock.adjust', $product) }}">Stock</flux:link>.</flux:text>
    @endif

    <form wire:submit="save" class="flex max-w-2xl flex-col gap-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="sku" label="SKU" required />
            <flux:input wire:model="slug" label="Slug" required />
        </div>

        <flux:input wire:model.blur="name" label="Nombre" required />
        <flux:textarea wire:model="description" label="Descripción" rows="3" />

        <flux:select wire:model="categoryId" label="Categoría">
            <flux:select.option value="">Sin categoría</flux:select.option>
            @foreach ($categories as $category)
                <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="grid gap-4 sm:grid-cols-3">
            <flux:input wire:model.live.debounce.300ms="price" label="Precio final (IVA incluido)" required />
            <flux:input wire:model="cost" label="Costo (neto)" />
            <flux:input wire:model.live.debounce.300ms="taxRate" label="IVA %" />
        </div>

        @if ($breakdown !== null)
            <flux:text>Desglose: neto $ {{ $breakdown['net'] }} + IVA $ {{ $breakdown['tax'] }}</flux:text>
        @endif

        <flux:switch wire:model="isActive" label="Activo" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">Guardar</flux:button>
            <flux:button href="{{ route('admin.products.index') }}">Cancelar</flux:button>
        </div>
    </form>
</div>
