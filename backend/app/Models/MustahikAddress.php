<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['mustahik_id', 'address_type', 'address_line', 'province_code', 'regency_code', 'district_code', 'village_code', 'postal_code', 'latitude', 'longitude', 'is_primary'])]
class MustahikAddress extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return ['is_primary' => 'boolean', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7'];
    }

    public function mustahik()
    {
        return $this->belongsTo(Mustahik::class);
    }
}
