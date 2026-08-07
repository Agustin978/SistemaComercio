<x-layouts.app title="Sistema Comercio">
    <div class="flex flex-col gap-4">
        <flux:heading size="xl">Storefront</flux:heading>

        <flux:text>Área pública. Cualquiera puede ver esta página, esté autenticado o no.</flux:text>

        @guest
            <div class="flex gap-4">
                <flux:button href="{{ route('login') }}" variant="primary">Iniciar sesión</flux:button>
                <flux:button href="{{ route('register') }}">Crear cuenta</flux:button>
            </div>
        @else
            <flux:callout variant="success" icon="check-circle">
                <flux:callout.heading>Autenticado como {{ auth()->user()->name }}</flux:callout.heading>
                <flux:callout.text>Rol: {{ auth()->user()->getRoleNames()->implode(', ') ?: 'sin rol' }}</flux:callout.text>
            </flux:callout>
        @endguest
    </div>
</x-layouts.app>
