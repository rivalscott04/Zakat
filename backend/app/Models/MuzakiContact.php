<?php

namespace App\Models;

use App\Enums\MuzakiContactType;
use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** PRD 03 §17 — contact disimpan terenkripsi dan disoft-delete untuk histori. */
class MuzakiContact extends Model
{
    use HasUlids, SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['value_encrypted', 'value_hash'];

    protected function casts(): array
    {
        return [
            'contact_type' => MuzakiContactType::class,
            'value_encrypted' => 'encrypted',
            'is_primary' => 'boolean',
            'verification_status' => VerificationStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    public function muzaki(): BelongsTo
    {
        return $this->belongsTo(Muzaki::class);
    }
}
