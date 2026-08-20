<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['transfer_number', 'source_fund_id', 'destination_fund_id', 'amount', 'currency', 'reason', 'status', 'approved_by', 'approved_at', 'transferred_at', 'requested_by'])]
class FundTransfer extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'transferred_at' => 'datetime'];
    }
}
