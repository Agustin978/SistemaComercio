<?php

namespace App\Reporting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportIngestionDivergence extends Model
{
    protected $fillable = ['report_ingestion_id', 'payload_hash', 'payload'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    /**
     * @return BelongsTo<ReportIngestion, $this>
     */
    public function reportIngestion(): BelongsTo
    {
        return $this->belongsTo(ReportIngestion::class);
    }
}
