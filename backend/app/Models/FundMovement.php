<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['movement_number', 'fund_id', 'movement_type', 'direction', 'amount', 'currency', 'source_type', 'source_id', 'reference_number', 'description', 'status', 'effective_at', 'created_by'])]
class FundMovement extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'effective_at' => 'datetime'];
    }

    public function fund()
    {
        return $this->belongsTo(Fund::class);
    }
}
