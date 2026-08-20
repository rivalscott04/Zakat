<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['adjustment_number', 'fund_id', 'adjustment_type', 'amount', 'currency', 'reason', 'reference', 'status'])]
class FundAdjustment extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }
}
