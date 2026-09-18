<div class="flex flex-col gap-6">
    <flux:heading size="xl">{{ $category === null ? 'Nueva categoría' : "Editar «{$category->name}»" }}</flux:heading>

    <form wire:submit="save" class="flex max-w-xl flex-col gap-4">
        <flux:input wire:model.blur="name" label="Nombre" required />
        <flux:input wire:model="slug" label="Slug" required />

        <flux:select wire:model="parentId" label="Categoría padre">
            <flux:select.option value="">Ninguna (raíz)</flux:select.option>
            @foreach ($parents as $parent)
                <flux:select.option value="{{ $parent->id }}">{{ $parent->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">Guardar</flux:button>
            <flux:button href="{{ route('admin.categories.index') }}">Cancelar</flux:button>
        </div>
    </form>
</div>
