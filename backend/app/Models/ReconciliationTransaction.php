<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['source_type', 'source_id', 'transaction_reference', 'transaction_date', 'amount', 'currency', 'direction', 'status'])]
class ReconciliationTransaction extends Model
{
    use BelongsToOrganization,HasUlids;

    protected function casts(): array
    {
        return ['transaction_date' => 'date', 'amount' => 'decimal:2'];
    }
}
