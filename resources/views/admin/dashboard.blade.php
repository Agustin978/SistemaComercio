<x-layouts.app title="Panel del comerciante">
    <div class="flex flex-col gap-4">
        <flux:heading size="xl">Panel del comerciante</flux:heading>

        <flux:callout variant="success" icon="check-circle">
            <flux:callout.heading>Acceso autorizado</flux:callout.heading>
            <flux:callout.text>
                {{ auth()->user()->name }} — rol: {{ auth()->user()->getRoleNames()->implode(', ') }}
            </flux:callout.text>
        </flux:callout>

        <flux:text>Esta sección todavía no tiene funcionalidad de negocio (Fase 2/3).</flux:text>
    </div>
</x-layouts.app>
