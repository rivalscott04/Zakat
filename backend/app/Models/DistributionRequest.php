<?php

namespace App\Models;

use App\Enums\DistributionType;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** PRD 12F §15. */
#[Fillable(['mustahik_id', 'program_id', 'assessment_id', 'fund_id', 'distribution_type', 'requested_amount', 'reason', 'priority'])]
class DistributionRequest extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return ['distribution_type' => DistributionType::class, 'requested_at' => 'datetime', 'reviewed_at' => 'datetime', 'requested_amount' => 'decimal:2'];
    }

    public function mustahik()
    {
        return $this->belongsTo(Mustahik::class);
    }

    public function fund()
    {
        return $this->belongsTo(Fund::class);
    }

    public function distribution()
    {
        return $this->belongsTo(Distribution::class);
    }
}
