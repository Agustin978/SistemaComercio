<x-layouts.guest>
    <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-6">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}" />

        <flux:heading size="lg">Restablecer contraseña</flux:heading>

        @if ($errors->any())
            <flux:callout variant="danger" icon="exclamation-triangle">
                <flux:callout.text>{{ $errors->first() }}</flux:callout.text>
            </flux:callout>
        @endif

        <flux:field>
            <flux:label>Correo electrónico</flux:label>
            <flux:input type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" />
        </flux:field>

        <flux:field>
            <flux:label>Nueva contraseña</flux:label>
            <flux:input type="password" name="password" required autocomplete="new-password" />
        </flux:field>

        <flux:field>
            <flux:label>Confirmar nueva contraseña</flux:label>
            <flux:input type="password" name="password_confirmation" required autocomplete="new-password" />
        </flux:field>

        <flux:button type="submit" variant="primary">Restablecer contraseña</flux:button>
    </form>
</x-layouts.guest>
