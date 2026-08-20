<?php

namespace App\Models;

use App\Enums\IdentityType;
use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** identity_number_encrypted tidak di-audit dan hanya diakses lewat service berizin. */
class MuzakiIdentity extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected $hidden = ['identity_number_encrypted', 'identity_number_hash'];

    protected function casts(): array
    {
        return [
            'identity_type' => IdentityType::class,
            'identity_number_encrypted' => 'encrypted',
            'verification_status' => VerificationStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    public function muzaki(): BelongsTo
    {
        return $this->belongsTo(Muzaki::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
