<div class="flex flex-col gap-6">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <flux:select wire:model.live="reportedSystemId" label="Sistema">
            <flux:select.option value="">Todos</flux:select.option>
            @foreach ($systems as $system)
                <flux:select.option value="{{ $system->id }}">{{ $system->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="origin" label="Origen">
            <flux:select.option value="">Todos</flux:select.option>
            @foreach ($origins as $originOption)
                <flux:select.option value="{{ $originOption->value }}">{{ $originOption->value }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="level" label="Nivel">
            <flux:select.option value="">Todos</flux:select.option>
            @foreach ($levels as $levelOption)
                <flux:select.option value="{{ $levelOption->value }}">{{ $levelOption->value }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input type="date" wire:model.live="from" label="Desde" />

        <flux:input type="date" wire:model.live="to" label="Hasta" />
    </div>

    <flux:table :paginate="$logs">
        <flux:table.columns>
            <flux:table.column>Fecha</flux:table.column>
            <flux:table.column>Sistema</flux:table.column>
            <flux:table.column>Origen</flux:table.column>
            <flux:table.column>Nivel</flux:table.column>
            <flux:table.column>Mensaje</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($logs as $log)
                <flux:table.row :key="$log->id">
                    <flux:table.cell>{{ $log->logged_at->format('Y-m-d H:i:s P') }}</flux:table.cell>
                    <flux:table.cell>{{ $log->reportedSystem->name }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$this->originColor($log->origin)">{{ $log->origin->value }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$this->levelColor($log->level)">{{ $log->level->value }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-normal">{{ $log->message }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">Sin registros para los filtros seleccionados.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
