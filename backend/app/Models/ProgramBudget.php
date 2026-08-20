<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['program_id', 'fund_id', 'budget_amount', 'currency', 'allocated_amount', 'committed_amount', 'disbursed_amount', 'remaining_amount', 'status'])]
class ProgramBudget extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return ['budget_amount' => 'decimal:2', 'allocated_amount' => 'decimal:2', 'committed_amount' => 'decimal:2', 'disbursed_amount' => 'decimal:2', 'remaining_amount' => 'decimal:2'];
    }
}
