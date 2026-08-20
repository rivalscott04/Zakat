<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** PRD 12K §28. */
#[Fillable(['distribution_id', 'item_code', 'item_name', 'description', 'quantity', 'unit', 'unit_value', 'total_value'])]
class DistributionItem extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'unit_value' => 'decimal:2', 'total_value' => 'decimal:2'];
    }
}
