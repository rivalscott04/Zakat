<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['account_code', 'account_name', 'account_type', 'account_category', 'parent_id', 'normal_balance', 'is_postable', 'status', 'description'])]
class ChartOfAccount extends Model
{
    use BelongsToOrganization, HasUlids, SoftDeletes;

    protected function casts(): array
    {
        return ['is_postable' => 'boolean'];
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
