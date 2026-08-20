<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['program_code', 'name', 'short_name', 'description', 'category_id', 'program_type', 'start_date', 'end_date', 'target_beneficiary', 'capacity_limit', 'waitlist_enabled', 'status', 'visibility', 'created_by', 'archived_at'])]
class Program extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'archived_at' => 'datetime', 'waitlist_enabled' => 'boolean'];
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(ProgramBudget::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ProgramEnrollment::class);
    }

    public function periods(): HasMany
    {
        return $this->hasMany(ProgramPeriod::class);
    }

    public function funds(): HasMany
    {
        return $this->hasMany(ProgramFund::class);
    }

    public function eligibilityRules(): HasMany
    {
        return $this->hasMany(ProgramEligibilityRule::class);
    }

    public function waitlists(): HasMany
    {
        return $this->hasMany(ProgramWaitlist::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ProgramActivity::class);
    }

    public function targets(): HasMany
    {
        return $this->hasMany(ProgramTarget::class);
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(ProgramOutput::class);
    }

    public function outcomes(): HasMany
    {
        return $this->hasMany(ProgramOutcome::class);
    }

    public function commitments(): HasMany
    {
        return $this->hasMany(ProgramBudgetCommitment::class);
    }
}
