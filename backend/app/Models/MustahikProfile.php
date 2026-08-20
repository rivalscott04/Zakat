<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['mustahik_id', 'education_level', 'occupation', 'employment_status', 'monthly_income', 'monthly_expense', 'housing_status', 'house_condition', 'asset_summary', 'disability_status', 'health_condition_summary', 'notes'])]
class MustahikProfile extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return ['monthly_income' => 'decimal:2', 'monthly_expense' => 'decimal:2', 'asset_summary' => 'array'];
    }

    public function mustahik()
    {
        return $this->belongsTo(Mustahik::class);
    }
}
