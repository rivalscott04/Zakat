<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['program_id', 'target_type', 'name', 'target_value', 'current_value', 'unit', 'period_id'])]
class ProgramTarget extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return ['target_value' => 'decimal:2', 'current_value' => 'decimal:2'];
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}
