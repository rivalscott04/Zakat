<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ZakatCalculationSnapshot extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['zakat_type_snapshot' => 'array', 'zakat_rule_snapshot' => 'array', 'nisab_snapshot' => 'array', 'haul_snapshot' => 'array', 'rate_snapshot' => 'array', 'reference_value_snapshot' => 'array', 'parameter_snapshot' => 'array', 'formula_snapshot' => 'array', 'result_snapshot' => 'array'];
    }
}
