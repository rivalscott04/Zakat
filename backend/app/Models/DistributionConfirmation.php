<?php

namespace App\Models;

use App\Enums\DistributionConfirmationMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** PRD 12S §46. */
#[Fillable(['distribution_id', 'confirmation_method', 'confirmed_at', 'confirmed_by', 'confirmation_data', 'status'])]
class DistributionConfirmation extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return ['confirmation_method' => DistributionConfirmationMethod::class, 'confirmed_at' => 'datetime', 'confirmation_data' => 'array'];
    }
}
