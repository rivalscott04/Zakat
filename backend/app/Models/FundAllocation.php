<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['allocation_number', 'fund_id', 'target_type', 'target_id', 'amount', 'currency', 'status', 'allocated_at', 'approved_by', 'approved_at', 'reason', 'created_by'])]
class FundAllocation extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'allocated_at' => 'datetime', 'approved_at' => 'datetime'];
    }

    public function fund()
    {
        return $this->belongsTo(Fund::class);
    }
}
