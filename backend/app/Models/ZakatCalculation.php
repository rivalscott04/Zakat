<?php

namespace App\Models;

use App\Enums\CalculationStatus;
use App\Enums\EligibilityStatus;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['calculation_number', 'muzaki_id', 'zakat_type_id', 'zakat_rule_id', 'rule_version', 'calculation_date', 'valid_until', 'currency'])]
class ZakatCalculation extends Model
{
    use BelongsToOrganization, HasUlids, SoftDeletes;

    public static function businessCode(): string
    {
        return 'ZKC';
    }

    protected function casts(): array
    {
        return [
            'status' => CalculationStatus::class,
            'eligibility_status' => EligibilityStatus::class,
            'calculation_date' => 'date',
            'valid_until' => 'date',
            'gross_amount' => 'decimal:8',
            'deduction_amount' => 'decimal:8',
            'net_amount' => 'decimal:8',
            'nisab_amount' => 'decimal:8',
            'zakat_rate' => 'decimal:8',
            'zakat_amount' => 'decimal:8',
            'result_data' => 'array',
        ];
    }

    public function inputs()
    {
        return $this->hasMany(ZakatCalculationInput::class, 'calculation_id');
    }

    public function snapshot()
    {
        return $this->hasOne(ZakatCalculationSnapshot::class, 'calculation_id');
    }

    public function adjustments()
    {
        return $this->hasMany(ZakatCalculationAdjustment::class, 'calculation_id');
    }

    public function versions()
    {
        return $this->hasMany(ZakatCalculationVersion::class, 'calculation_id');
    }

    public function muzaki()
    {
        return $this->belongsTo(Muzaki::class);
    }

    public function type()
    {
        return $this->belongsTo(ZakatType::class, 'zakat_type_id');
    }

    public function rule()
    {
        return $this->belongsTo(ZakatRule::class, 'zakat_rule_id');
    }
}
