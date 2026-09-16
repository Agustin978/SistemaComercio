<?php

namespace App\Reporting\Livewire\Hub;

use App\Reporting\Contracts\LogLevel;
use App\Reporting\Enums\LogOrigin;
use App\Reporting\Models\ReportedSystem;
use App\Reporting\Models\SystemLog;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

final class LogViewer extends Component
{
    use WithPagination;

    public ?int $reportedSystemId = null;

    public ?string $origin = null;

    public ?string $level = null;

    public ?string $from = null;

    public ?string $to = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('super_admin') === true, 403);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['reportedSystemId', 'origin', 'level', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function render(): View
    {
        $query = SystemLog::query()->with('reportedSystem');

        if ($this->reportedSystemId !== null) {
            $query->where('reported_system_id', $this->reportedSystemId);
        }

        if (filled($this->origin)) {
            $query->where('origin', $this->origin);
        }

        if (filled($this->level)) {
            $query->where('level', $this->level);
        }

        $from = $this->parseDate($this->from);
        $to = $this->parseDate($this->to);

        if ($from !== null) {
            $query->where('logged_at', '>=', $from);
        }

        if ($to !== null) {
            $query->where('logged_at', '<', $to->addDay());
        }

        return view('reporting.hub.log-viewer', [
            'logs' => $query->orderByDesc('logged_at')->orderByDesc('id')->paginate(25),
            'systems' => ReportedSystem::query()->orderBy('name')->get(['id', 'name']),
            'origins' => LogOrigin::cases(),
            'levels' => LogLevel::cases(),
        ]);
    }

    public function originColor(LogOrigin $origin): string
    {
        return match ($origin) {
            LogOrigin::Hub => 'purple',
            LogOrigin::Emitter => 'zinc',
        };
    }

    public function levelColor(LogLevel $level): string
    {
        return match ($level) {
            LogLevel::Emergency, LogLevel::Alert, LogLevel::Critical => 'red',
            LogLevel::Error => 'orange',
            LogLevel::Warning => 'amber',
            LogLevel::Notice => 'blue',
            LogLevel::Info => 'sky',
            LogLevel::Debug => 'zinc',
        };
    }

    private function parseDate(?string $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('!Y-m-d', $value);
        } catch (InvalidFormatException) {
            return null;
        }
    }
}
