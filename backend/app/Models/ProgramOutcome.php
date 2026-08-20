<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['program_id', 'outcome_code', 'name', 'description', 'measurement_method', 'target_value', 'actual_value', 'unit', 'measurement_date', 'status'])]
class ProgramOutcome extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return ['target_value' => 'decimal:2', 'actual_value' => 'decimal:2', 'measurement_date' => 'date'];
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}
