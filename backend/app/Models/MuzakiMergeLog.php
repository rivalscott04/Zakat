<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MuzakiMergeLog extends Model
{
    use BelongsToOrganization, HasUlids;

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['source_snapshot' => 'array', 'target_snapshot' => 'array', 'merged_at' => 'datetime'];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Muzaki::class, 'source_muzaki_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Muzaki::class, 'target_muzaki_id');
    }
}
