<?php

namespace App\Models;

use App\Enums\FundStatus;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['fund_code', 'name', 'fund_type', 'category', 'restriction_type', 'status', 'currency', 'opening_balance', 'current_balance', 'available_balance', 'reserved_balance', 'allocated_balance', 'distributed_balance'])]
class Fund extends Model
{
    use BelongsToOrganization, HasUlids, SoftDeletes;

    protected function casts(): array
    {
        return ['status' => FundStatus::class, 'opening_balance' => 'decimal:2', 'current_balance' => 'decimal:2', 'available_balance' => 'decimal:2', 'reserved_balance' => 'decimal:2', 'allocated_balance' => 'decimal:2', 'distributed_balance' => 'decimal:2'];
    }

    public function movements()
    {
        return $this->hasMany(FundMovement::class);
    }

    public function allocations()
    {
        return $this->hasMany(FundAllocation::class);
    }

    public function reservations()
    {
        return $this->hasMany(FundReservation::class);
    }
}
