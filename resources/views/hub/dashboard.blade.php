<x-layouts.app title="Hub central">
    <div class="flex flex-col gap-6">
        <flux:heading size="xl">Hub central</flux:heading>

        <flux:text>Logs reportados por los sistemas asociados y eventos generados por el hub.</flux:text>

        @livewire(\App\Reporting\Livewire\Hub\LogViewer::class)
    </div>
</x-layouts.app>
