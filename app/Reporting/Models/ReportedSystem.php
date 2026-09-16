<?php

namespace App\Reporting\Models;

use Database\Factories\ReportedSystemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportedSystem extends Model
{
    /** @use HasFactory<ReportedSystemFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'is_active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * @return HasMany<ReportIngestion, $this>
     */
    public function reportIngestions(): HasMany
    {
        return $this->hasMany(ReportIngestion::class);
    }

    /**
     * @return HasMany<SystemLog, $this>
     */
    public function systemLogs(): HasMany
    {
        return $this->hasMany(SystemLog::class);
    }

    protected static function newFactory(): ReportedSystemFactory
    {
        return ReportedSystemFactory::new();
    }
}
