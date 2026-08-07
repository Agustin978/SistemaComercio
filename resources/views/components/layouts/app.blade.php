<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name') }}</title>

        @fluxAppearance

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
        <flux:header container class="border-b border-zinc-200 dark:border-zinc-700">
            <flux:brand href="/" name="{{ config('app.name') }}" />

            <flux:spacer />

            @auth
                <flux:dropdown>
                    <flux:profile :name="auth()->user()->name" />

                    <flux:menu>
                        <flux:menu.item disabled>{{ auth()->user()->getRoleNames()->implode(', ') ?: 'sin rol' }}</flux:menu.item>
                        <flux:menu.separator />
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <flux:menu.item as="button" type="submit">Cerrar sesión</flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            @endauth
        </flux:header>

        <flux:main container>
            {{ $slot }}
        </flux:main>

        @fluxScripts
    </body>
</html>
