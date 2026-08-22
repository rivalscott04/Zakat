<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * PRD 20 — System Settings.
 *
 * Sengaja tanpa BelongsToOrganization: baris global (organization_id NULL)
 * harus tetap terbaca di dalam context organisasi mana pun.
 */
class Setting extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }
}
