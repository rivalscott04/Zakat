<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['program_id', 'mustahik_id', 'assessment_id', 'result', 'score', 'matched_rules', 'override_reason', 'overridden_by', 'evaluated_at', 'evaluated_by'])]
class ProgramEligibilityEvaluation extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return ['score' => 'decimal:2', 'matched_rules' => 'array', 'evaluated_at' => 'datetime'];
    }

    public function mustahik()
    {
        return $this->belongsTo(Mustahik::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}
