<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['legal_name', 'registration_number', 'industry', 'representative_name', 'representative_position'])]
class MuzakiOrganizationProfile extends Model
{
    use Auditable, HasUlids;

    public function representatives(): HasMany
    {
        return $this->hasMany(MuzakiRepresentative::class, 'muzaki_id', 'muzaki_id');
    }

    public function auditPrefix(): string
    {
        return 'muzaki_organization_profile';
    }
}
