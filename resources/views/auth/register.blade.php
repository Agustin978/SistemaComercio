<x-layouts.guest>
    <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-6">
        @csrf

        <flux:heading size="lg">Crear cuenta</flux:heading>

        @if ($errors->any())
            <flux:callout variant="danger" icon="exclamation-triangle">
                <flux:callout.text>{{ $errors->first() }}</flux:callout.text>
            </flux:callout>
        @endif

        <flux:field>
            <flux:label>Nombre</flux:label>
            <flux:input type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" />
        </flux:field>

        <flux:field>
            <flux:label>Correo electrónico</flux:label>
            <flux:input type="email" name="email" value="{{ old('email') }}" required autocomplete="username" />
        </flux:field>

        <flux:field>
            <flux:label>Contraseña</flux:label>
            <flux:input type="password" name="password" required autocomplete="new-password" />
        </flux:field>

        <flux:field>
            <flux:label>Confirmar contraseña</flux:label>
            <flux:input type="password" name="password_confirmation" required autocomplete="new-password" />
        </flux:field>

        <flux:button type="submit" variant="primary">Registrarme</flux:button>

        <div class="text-sm">
            <flux:link href="{{ route('login') }}">Ya tengo cuenta</flux:link>
        </div>
    </form>
</x-layouts.guest>
