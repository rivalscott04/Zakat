<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ZakatCalculationInput extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['value' => 'array', 'normalized_value' => 'array'];
    }
}
