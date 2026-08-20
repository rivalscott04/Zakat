<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['program_id', 'mustahik_id', 'assessment_id', 'priority_score', 'position', 'status', 'added_at', 'processed_at'])]
class ProgramWaitlist extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return ['priority_score' => 'decimal:2', 'added_at' => 'datetime', 'processed_at' => 'datetime'];
    }
}
