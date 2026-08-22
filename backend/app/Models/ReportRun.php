<?php

namespace App\Models;

use App\Enums\ReportRunStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasBusinessNumber;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** PRD 19H §23. Snapshot bersifat immutable setelah completed (PRD 19W §14). */
class ReportRun extends Model
{
    use BelongsToOrganization, HasBusinessNumber, HasUlids;

    protected $guarded = [];

    public static function businessCode(): string
    {
        return 'RPR';
    }

    public function businessNumberColumn(): string
    {
        return 'run_number';
    }

    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'snapshot_data' => 'array',
            'status' => ReportRunStatus::class,
            'row_count' => 'integer',
            'generated_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function exports(): HasMany
    {
        return $this->hasMany(ReportExport::class);
    }
}
