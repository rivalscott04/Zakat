<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['request_number', 'mustahik_id', 'program_id', 'assessment_id', 'fund_id', 'distribution_type', 'requested_amount', 'currency', 'reason', 'priority', 'requested_by', 'requested_at', 'status'])]
class DistributionRequest extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return ['requested_at' => 'datetime', 'requested_amount' => 'decimal:2'];
    }

    public function mustahik()
    {
        return $this->belongsTo(Mustahik::class);
    }

    public function fund()
    {
        return $this->belongsTo(Fund::class);
    }
}
