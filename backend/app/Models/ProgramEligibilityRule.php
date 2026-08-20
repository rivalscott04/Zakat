<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['program_id', 'rule_code', 'rule_type', 'field', 'operator', 'value', 'weight', 'required', 'sort_order', 'status'])]
class ProgramEligibilityRule extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return ['value' => 'array', 'weight' => 'decimal:2', 'required' => 'boolean'];
    }
}
