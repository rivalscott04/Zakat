<?php

namespace App\Models;

use App\Enums\MuzakiStatus;
use App\Enums\MuzakiType;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasBusinessNumber;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/** PRD 03 §5 — entity inti contributor, terpisah dari transaksi. */
#[Fillable(['display_name', 'registration_source', 'registered_at'])]
class Muzaki extends Model
{
    use Auditable, BelongsToOrganization, HasBusinessNumber, HasUlids, SoftDeletes;

    public static function businessCode(): string
    {
        return 'MZK';
    }

    protected function casts(): array
    {
        return [
            'muzaki_type' => MuzakiType::class,
            'status' => MuzakiStatus::class,
            'registered_at' => 'datetime',
        ];
    }

    public function individualProfile(): HasOne
    {
        return $this->hasOne(MuzakiIndividualProfile::class);
    }

    public function organizationProfile(): HasOne
    {
        return $this->hasOne(MuzakiOrganizationProfile::class);
    }

    public function identities(): HasMany
    {
        return $this->hasMany(MuzakiIdentity::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(MuzakiContact::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(MuzakiAddress::class);
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(MuzakiFamilyMember::class);
    }

    public function preference(): HasOne
    {
        return $this->hasOne(MuzakiPreference::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(MuzakiTagAssignment::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(MuzakiNote::class);
    }
}
