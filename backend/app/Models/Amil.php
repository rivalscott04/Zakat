<?php

namespace App\Models;

use App\Enums\AmilStatus;
use App\Enums\AssignmentStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasBusinessNumber;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** PRD 02 §17 — amil. Boleh ada tanpa user account (PRD 02 §18). */
#[Fillable(['name', 'employee_number', 'email', 'phone', 'joined_at'])]
class Amil extends Model
{
    use Auditable, BelongsToOrganization, HasBusinessNumber, HasUlids, SoftDeletes;

    public static function businessCode(): string
    {
        return 'AML';
    }

    protected function casts(): array
    {
        return [
            'status' => AmilStatus::class,
            'joined_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AmilAssignment::class);
    }

    public function activeAssignments(): HasMany
    {
        return $this->assignments()->where('status', AssignmentStatus::Active);
    }
}
