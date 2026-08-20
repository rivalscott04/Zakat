<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['program_code', 'name', 'short_name', 'description', 'category_id', 'program_type', 'start_date', 'end_date', 'target_beneficiary', 'capacity_limit', 'status', 'visibility', 'created_by', 'archived_at'])]
class Program extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'archived_at' => 'datetime'];
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(ProgramBudget::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ProgramEnrollment::class);
    }
}
