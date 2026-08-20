<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['distribution_number', 'distribution_type', 'source_type', 'program_id', 'program_enrollment_id', 'mustahik_id', 'assessment_id', 'fund_id', 'currency', 'requested_amount', 'approved_amount', 'distributed_amount', 'distribution_date', 'scheduled_date', 'status', 'priority', 'description', 'created_by'])]
class Distribution extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return ['requested_amount' => 'decimal:2', 'approved_amount' => 'decimal:2', 'distributed_amount' => 'decimal:2', 'distribution_date' => 'date', 'scheduled_date' => 'date'];
    }

    public function mustahik()
    {
        return $this->belongsTo(Mustahik::class);
    }

    public function fund()
    {
        return $this->belongsTo(Fund::class);
    }

    public function items()
    {
        return $this->hasMany(DistributionItem::class);
    }
}
