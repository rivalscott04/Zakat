<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['request_number', 'mustahik_id', 'assessment_type', 'priority', 'reason', 'requested_by', 'requested_at', 'due_date', 'status', 'assessor_id', 'assigned_at', 'notes'])]
class AssessmentRequest extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return ['requested_at' => 'datetime', 'assigned_at' => 'datetime', 'due_date' => 'date'];
    }

    public function mustahik(): BelongsTo
    {
        return $this->belongsTo(Mustahik::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }
}
