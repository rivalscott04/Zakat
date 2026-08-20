<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ZakatHaul extends Model
{
    use HasUlids;

    protected $guarded = [];

    public function rule()
    {
        return $this->belongsTo(ZakatRule::class, 'zakat_rule_id');
    }
}
