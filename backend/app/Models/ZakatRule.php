<?php

namespace App\Models;

use App\Enums\ZakatStatus;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['zakat_type_id', 'rule_code', 'name', 'description', 'version', 'effective_from', 'effective_until'])]
class ZakatRule extends Model
{
    use BelongsToOrganization, HasUlids, SoftDeletes;

    protected function casts(): array
    {
        return ['status' => ZakatStatus::class, 'effective_from' => 'date', 'effective_until' => 'date'];
    }

    public function type()
    {
        return $this->belongsTo(ZakatType::class, 'zakat_type_id');
    }

    public function rates()
    {
        return $this->hasMany(ZakatRate::class);
    }

    public function nisab()
    {
        return $this->hasOne(ZakatNisab::class);
    }

    public function haul()
    {
        return $this->hasOne(ZakatHaul::class);
    }

    public function parameters() { return $this->hasMany(ZakatRuleParameter::class); }
}
