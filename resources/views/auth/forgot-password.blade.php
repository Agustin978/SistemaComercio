<x-layouts.guest>
    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
        @csrf

        <flux:heading size="lg">Recuperar contraseña</flux:heading>

        <flux:text>Ingresá tu correo y te enviamos un enlace para restablecer tu contraseña.</flux:text>

        @if (session('status'))
            <flux:callout variant="success" icon="check-circle">
                <flux:callout.text>{{ session('status') }}</flux:callout.text>
            </flux:callout>
        @endif

        @if ($errors->any())
            <flux:callout variant="danger" icon="exclamation-triangle">
                <flux:callout.text>{{ $errors->first() }}</flux:callout.text>
            </flux:callout>
        @endif

        <flux:field>
            <flux:label>Correo electrónico</flux:label>
            <flux:input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" />
        </flux:field>

        <flux:button type="submit" variant="primary">Enviar enlace</flux:button>

        <div class="text-sm">
            <flux:link href="{{ route('login') }}">Volver a iniciar sesión</flux:link>
        </div>
    </form>
</x-layouts.guest>
