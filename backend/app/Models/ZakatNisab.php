<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ZakatNisab extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['reference_value' => 'decimal:8', 'quantity' => 'decimal:8', 'effective_from' => 'date', 'effective_until' => 'date'];
    }

    public function rule()
    {
        return $this->belongsTo(ZakatRule::class, 'zakat_rule_id');
    }
}
