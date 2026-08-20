<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MuzakiAddress extends Model
{
    use HasUlids, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7'];
    }

    public function muzaki(): BelongsTo
    {
        return $this->belongsTo(Muzaki::class);
    }
}
