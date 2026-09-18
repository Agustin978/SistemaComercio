<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">Categorías</flux:heading>
        <flux:button href="{{ route('admin.categories.create') }}" variant="primary">Nueva categoría</flux:button>
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle">
            <flux:callout.text>{{ session('status') }}</flux:callout.text>
        </flux:callout>
    @endif

    @if ($error)
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.text>{{ $error }}</flux:callout.text>
        </flux:callout>
    @endif

    @if ($reassigningId !== null)
        <flux:card class="flex flex-col gap-4">
            <flux:heading size="lg">Reasignar productos borrados</flux:heading>
            <flux:select wire:model="reassignTarget" label="Destino">
                <flux:select.option value="">Elegí un destino</flux:select.option>
                <flux:select.option value="none">Sin categoría</flux:select.option>
                @foreach ($targets as $row)
                    <flux:select.option value="{{ $row['category']->id }}">{{ str_repeat('— ', $row['depth']) }}{{ $row['category']->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <div class="flex gap-2">
                <flux:button wire:click="confirmReassign" variant="primary" :disabled="$reassignTarget === ''">Reasignar y borrar</flux:button>
                <flux:button wire:click="cancelReassign">Cancelar</flux:button>
            </div>
        </flux:card>
    @endif

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Nombre</flux:table.column>
            <flux:table.column>Slug</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($tree as $row)
                <flux:table.row :key="$row['category']->id">
                    <flux:table.cell>
                        <span style="padding-left: {{ $row['depth'] * 1.25 }}rem">{{ $row['category']->name }}</span>
                    </flux:table.cell>
                    <flux:table.cell>{{ $row['category']->slug }}</flux:table.cell>
                    <flux:table.cell class="flex gap-2">
                        <flux:button size="sm" href="{{ route('admin.categories.edit', $row['category']) }}">Editar</flux:button>
                        <flux:button size="sm" variant="danger" wire:click="delete({{ $row['category']->id }})" wire:confirm="¿Borrar la categoría «{{ $row['category']->name }}»?">Borrar</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="3">Todavía no hay categorías.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
