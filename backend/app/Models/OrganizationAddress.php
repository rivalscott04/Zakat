<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** PRD 02 §22 — alamat organisasi. */
#[Fillable([
    'label', 'address_line_1', 'address_line_2', 'country_code', 'province_code',
    'city_code', 'district_code', 'village_code', 'postal_code', 'latitude',
    'longitude', 'is_primary',
])]
class OrganizationAddress extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }
}
