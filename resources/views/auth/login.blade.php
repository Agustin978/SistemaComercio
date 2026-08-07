<x-layouts.guest>
    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-6">
        @csrf

        <flux:heading size="lg">Iniciar sesión</flux:heading>

        @if ($errors->any())
            <flux:callout variant="danger" icon="exclamation-triangle">
                <flux:callout.text>{{ $errors->first() }}</flux:callout.text>
            </flux:callout>
        @endif

        <flux:field>
            <flux:label>Correo electrónico</flux:label>
            <flux:input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" />
        </flux:field>

        <flux:field>
            <flux:label>Contraseña</flux:label>
            <flux:input type="password" name="password" required autocomplete="current-password" />
        </flux:field>

        <flux:field variant="inline">
            <flux:checkbox name="remember" />
            <flux:label>Recordarme</flux:label>
        </flux:field>

        <flux:button type="submit" variant="primary">Ingresar</flux:button>

        <div class="flex justify-between text-sm">
            <flux:link href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</flux:link>
            <flux:link href="{{ route('register') }}">Crear cuenta</flux:link>
        </div>
    </form>
</x-layouts.guest>
