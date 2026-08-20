<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * PRD 01 §25 — permission. Format nama `resource.action` sesuai keputusan user
 * 2026-08-20 yang menyelesaikan konflik Core PRD §21 vs PRD 01 §25.
 */
#[Fillable(['module', 'resource', 'action', 'name', 'description'])]
class Permission extends Model
{
    use HasUlids;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
