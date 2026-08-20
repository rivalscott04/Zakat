<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['reservation_number', 'fund_id', 'target_type', 'target_id', 'amount', 'currency', 'status', 'reserved_at', 'expires_at', 'released_at', 'reason', 'created_by'])]
class FundReservation extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'reserved_at' => 'datetime', 'expires_at' => 'datetime', 'released_at' => 'datetime'];
    }

    public function fund()
    {
        return $this->belongsTo(Fund::class);
    }
}
