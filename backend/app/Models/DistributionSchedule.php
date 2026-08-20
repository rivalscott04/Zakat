<?php

namespace App\Models;

use App\Enums\DistributionScheduleType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** PRD 12N §34. */
#[Fillable(['distribution_id', 'schedule_type', 'scheduled_date', 'amount', 'status', 'processed_at'])]
class DistributionSchedule extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return ['schedule_type' => DistributionScheduleType::class, 'scheduled_date' => 'date', 'amount' => 'decimal:2', 'processed_at' => 'datetime'];
    }
}
