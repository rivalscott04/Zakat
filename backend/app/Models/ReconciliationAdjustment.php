<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['reconciliation_session_id', 'bank_transaction_id', 'adjustment_type', 'amount', 'reason', 'reference', 'status', 'created_by', 'approved_by'])]
class ReconciliationAdjustment extends Model
{
    use BelongsToOrganization,HasUlids;

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }
}
