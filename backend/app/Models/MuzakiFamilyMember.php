<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MuzakiFamilyMember extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'is_head' => 'boolean'];
    }

    public function muzaki(): BelongsTo
    {
        return $this->belongsTo(Muzaki::class);
    }
}
