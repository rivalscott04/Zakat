<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** PRD 01 §23 — role. organization_id NULL berarti role system-level. */
#[Fillable(['name', 'code', 'description', 'is_active'])]
class Role extends Model
{
    use Auditable, HasUlids;

    public const SUPER_ADMIN = 'SUPER_ADMIN';

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('organization_id')->withTimestamps();
    }

    public function isSystem(): bool
    {
        return $this->is_system;
    }
}
