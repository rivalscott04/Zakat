<?php

namespace App\Models;

use App\Enums\EntityStatus;
use App\Enums\ReportExportFormat;
use App\Enums\ReportFrequency;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** PRD 19K §33. */
#[Fillable(['report_id', 'name', 'frequency', 'schedule_configuration', 'parameters', 'output_format', 'recipient_configuration'])]
class ReportSchedule extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return [
            'frequency' => ReportFrequency::class,
            'output_format' => ReportExportFormat::class,
            'schedule_configuration' => 'array',
            'parameters' => 'array',
            'recipient_configuration' => 'array',
            'status' => EntityStatus::class,
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}
