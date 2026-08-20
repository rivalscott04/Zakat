<?php

namespace App\Models;

use App\Enums\CalculationMethod;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['zakat_category_id', 'code', 'name', 'description', 'calculation_method', 'status', 'sort_order'])]
class ZakatType extends Model
{
    use BelongsToOrganization, HasUlids, SoftDeletes;

    protected function casts(): array
    {
        return ['calculation_method' => CalculationMethod::class];
    }

    public function category()
    {
        return $this->belongsTo(ZakatCategory::class, 'zakat_category_id');
    }

    public function rules()
    {
        return $this->hasMany(ZakatRule::class);
    }
}
