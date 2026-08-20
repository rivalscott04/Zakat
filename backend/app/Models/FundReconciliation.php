<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['reconciliation_number', 'fund_id', 'reconciliation_date', 'system_balance', 'external_balance', 'difference_amount', 'status', 'notes', 'created_by'])]
class FundReconciliation extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return ['reconciliation_date' => 'date', 'system_balance' => 'decimal:2', 'external_balance' => 'decimal:2', 'difference_amount' => 'decimal:2'];
    }
}
