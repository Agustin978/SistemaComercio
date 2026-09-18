<div class="flex flex-col gap-6">
    <flux:heading size="xl">Panel del comerciante</flux:heading>

    <div class="grid gap-4 sm:grid-cols-3">
        <flux:card>
            <flux:text>Productos</flux:text>
            <flux:heading size="xl">{{ $productCount }}</flux:heading>
            <flux:link href="{{ route('admin.products.index') }}">Ver productos</flux:link>
        </flux:card>

        <flux:card>
            <flux:text>Categorías</flux:text>
            <flux:heading size="xl">{{ $categoryCount }}</flux:heading>
            <flux:link href="{{ route('admin.categories.index') }}">Ver categorías</flux:link>
        </flux:card>

        <flux:card>
            <flux:text>Activos sin stock</flux:text>
            <flux:heading size="xl">{{ $outOfStockCount }}</flux:heading>
            <flux:link href="{{ route('admin.products.index', ['stock' => 'out']) }}">Revisar</flux:link>
        </flux:card>
    </div>
</div>
