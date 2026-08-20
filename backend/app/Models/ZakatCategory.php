<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['code', 'name', 'description', 'status', 'sort_order'])]
class ZakatCategory extends Model
{
    use BelongsToOrganization, HasUlids, SoftDeletes;

    public function types()
    {
        return $this->hasMany(ZakatType::class);
    }
}
