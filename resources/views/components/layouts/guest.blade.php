<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>

        @fluxAppearance

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
        <div class="flex min-h-screen flex-col items-center justify-center gap-6 p-6">
            <flux:heading size="xl">{{ config('app.name') }}</flux:heading>

            <flux:card class="w-full max-w-sm">
                {{ $slot }}
            </flux:card>
        </div>

        @fluxScripts
    </body>
</html>
