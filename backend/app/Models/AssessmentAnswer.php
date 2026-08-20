<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['assessment_id', 'question_id', 'question_code', 'answer_value', 'answer_data', 'score', 'notes', 'question_snapshot'])]
class AssessmentAnswer extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return ['answer_data' => 'array', 'score' => 'decimal:2', 'question_snapshot' => 'array'];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }
}
