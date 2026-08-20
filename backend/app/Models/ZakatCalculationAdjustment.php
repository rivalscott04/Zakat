<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ZakatCalculationAdjustment extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['original_amount' => 'decimal:8', 'adjustment_amount' => 'decimal:8', 'final_amount' => 'decimal:8'];
    }
}
