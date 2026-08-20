<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['mustahik_id', 'identity_type', 'identity_number_encrypted', 'identity_number_hash', 'identity_name', 'verification_status', 'verified_at', 'verified_by'])]
class MustahikIdentity extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $hidden = ['identity_number_encrypted', 'identity_number_hash'];

    protected function casts(): array
    {
        return ['identity_number_encrypted' => 'encrypted', 'verified_at' => 'datetime'];
    }

    public function mustahik()
    {
        return $this->belongsTo(Mustahik::class);
    }
}
