<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['assessment_number', 'assessment_request_id', 'mustahik_id', 'template_id', 'template_version', 'assessment_type', 'assessor_id', 'assessment_date', 'started_at', 'submitted_at', 'approved_at', 'status', 'total_score', 'result', 'recommendation', 'recommendation_reason', 'review_notes', 'previous_assessment_id'])]
class Assessment extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return ['assessment_date' => 'date', 'started_at' => 'datetime', 'submitted_at' => 'datetime', 'approved_at' => 'datetime', 'total_score' => 'decimal:2'];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(AssessmentRequest::class, 'assessment_request_id');
    }

    public function mustahik(): BelongsTo
    {
        return $this->belongsTo(Mustahik::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AssessmentTemplate::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AssessmentAnswer::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(AssessmentReview::class);
    }
}
