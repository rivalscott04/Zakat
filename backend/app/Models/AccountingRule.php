<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['rule_code', 'name', 'event_type', 'debit_account_id', 'credit_account_id', 'condition_data', 'priority', 'status', 'effective_from', 'effective_until'])]
class AccountingRule extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return ['condition_data' => 'array', 'effective_from' => 'date', 'effective_until' => 'date'];
    }
}
