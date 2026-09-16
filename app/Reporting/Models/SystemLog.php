<?php

namespace App\Reporting\Models;

use App\Reporting\Contracts\LogLevel;
use App\Reporting\Enums\LogOrigin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemLog extends Model
{
    protected $fillable = [
        'report_ingestion_id',
        'reported_system_id',
        'origin',
        'level',
        'message',
        'context',
        'logged_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'origin' => LogOrigin::class,
            'level' => LogLevel::class,
            'context' => 'array',
            'logged_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<ReportIngestion, $this>
     */
    public function reportIngestion(): BelongsTo
    {
        return $this->belongsTo(ReportIngestion::class);
    }

    /**
     * @return BelongsTo<ReportedSystem, $this>
     */
    public function reportedSystem(): BelongsTo
    {
        return $this->belongsTo(ReportedSystem::class);
    }
}
