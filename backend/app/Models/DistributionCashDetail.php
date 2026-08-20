<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** PRD 12L §29. */
#[Fillable(['distribution_id', 'amount', 'currency', 'cashier_id', 'disbursed_at', 'receipt_number'])]
class DistributionCashDetail extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'disbursed_at' => 'datetime'];
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }
}
