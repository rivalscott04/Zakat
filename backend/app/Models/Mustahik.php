<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['mustahik_number', 'mustahik_type', 'full_name', 'display_name', 'gender', 'birth_date', 'marital_status', 'phone', 'email', 'identity_type', 'identity_number_hash', 'status', 'verification_status', 'eligibility_status', 'registered_at', 'registered_by'])]
class Mustahik extends Model
{
    use BelongsToOrganization, HasUlids, SoftDeletes;

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'registered_at' => 'date'];
    }

    public function identities()
    {
        return $this->hasMany(MustahikIdentity::class);
    }

    public function addresses()
    {
        return $this->hasMany(MustahikAddress::class);
    }

    public function asnaf()
    {
        return $this->hasMany(MustahikAsnaf::class);
    }

    public function profile()
    {
        return $this->hasOne(MustahikProfile::class);
    }
}
