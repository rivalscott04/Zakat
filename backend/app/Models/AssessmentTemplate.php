<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['template_code', 'name', 'description', 'assessment_type', 'mustahik_type', 'version', 'status', 'effective_from', 'effective_until', 'schema'])]
class AssessmentTemplate extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return ['effective_from' => 'date', 'effective_until' => 'date', 'schema' => 'array'];
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'template_id');
    }
}
