<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['program_id', 'output_code', 'name', 'target_value', 'actual_value', 'unit', 'period_id', 'status'])]
class ProgramOutput extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return ['target_value' => 'decimal:2', 'actual_value' => 'decimal:2'];
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}
