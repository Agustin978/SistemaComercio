<?php

namespace App\Reporting\Models;

use App\Reporting\Contracts\ReportType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportIngestion extends Model
{
    protected $fillable = ['reported_system_id', 'report_type', 'idempotency_key', 'payload_hash', 'payload'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'report_type' => ReportType::class,
            'payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<ReportedSystem, $this>
     */
    public function reportedSystem(): BelongsTo
    {
        return $this->belongsTo(ReportedSystem::class);
    }

    /**
     * @return HasMany<SystemLog, $this>
     */
    public function systemLogs(): HasMany
    {
        return $this->hasMany(SystemLog::class);
    }

    /**
     * @return HasMany<ReportIngestionDivergence, $this>
     */
    public function divergences(): HasMany
    {
        return $this->hasMany(ReportIngestionDivergence::class);
    }
}
