<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** PRD 12Q §42. */
#[Fillable(['batch_id', 'distribution_id', 'mustahik_id', 'approved_amount', 'distributed_amount', 'status', 'failure_reason', 'failure_note'])]
class DistributionBeneficiary extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return ['approved_amount' => 'decimal:2', 'distributed_amount' => 'decimal:2'];
    }

    public function mustahik()
    {
        return $this->belongsTo(Mustahik::class);
    }

    public function distribution()
    {
        return $this->belongsTo(Distribution::class);
    }
}
