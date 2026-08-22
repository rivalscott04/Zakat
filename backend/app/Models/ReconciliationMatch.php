<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['bank_transaction_id', 'reconciliation_transaction_id', 'match_type', 'matched_amount', 'confidence_score', 'matched_by', 'matched_at', 'status'])]
class ReconciliationMatch extends Model
{
    use BelongsToOrganization,HasUlids;

    protected function casts(): array
    {
        return ['matched_amount' => 'decimal:2', 'confidence_score' => 'decimal:2', 'matched_at' => 'datetime'];
    }
}
