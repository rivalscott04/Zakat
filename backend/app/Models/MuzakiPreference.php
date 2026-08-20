<?php

namespace App\Models;

use App\Enums\PublicVisibility;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MuzakiPreference extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['allow_email' => 'boolean', 'allow_sms' => 'boolean', 'allow_whatsapp' => 'boolean', 'public_visibility' => PublicVisibility::class];
    }

    public function muzaki(): BelongsTo
    {
        return $this->belongsTo(Muzaki::class);
    }
}
