<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['program_id', 'program_budget_id', 'enrollment_id', 'distribution_id', 'amount', 'currency', 'status', 'reason', 'created_by', 'created_at', 'updated_at'])]
class ProgramBudgetCommitment extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
    }

    public function getTable()
    {
        return 'program_budget_commitments';
    }

    public function programBudget()
    {
        return $this->belongsTo(ProgramBudget::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}
