<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ZakatRate extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['rate_value' => 'decimal:8', 'effective_from' => 'date', 'effective_until' => 'date'];
    }

    public function rule()
    {
        return $this->belongsTo(ZakatRule::class, 'zakat_rule_id');
    }
}
