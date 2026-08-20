<?php

namespace App\Services;

use App\Enums\CalculationMethod;
use App\Enums\CalculationStatus;
use App\Enums\EligibilityStatus;
use App\Enums\ZakatStatus;
use App\Exceptions\ZakatException;
use App\Models\Muzaki;
use App\Models\Scopes\OrganizationScope;
use App\Models\ZakatAgricultureConfiguration;
use App\Models\ZakatCalculation;
use App\Models\ZakatCalculationAdjustment;
use App\Models\ZakatCalculationInput;
use App\Models\ZakatCalculationSnapshot;
use App\Models\ZakatCalculationVersion;
use App\Models\ZakatLivestockConfiguration;
use App\Models\ZakatReferenceValue;
use App\Models\ZakatRule;
use App\Models\ZakatType;
use App\Support\OrganizationContext;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ZakatCalculationService
{
    public function __construct(private readonly AuditService $audit) {}

    public function list(array $filters): LengthAwarePaginator
    {
        $this->expireDueCalculations();

        return ZakatCalculation::with(['muzaki:id,display_name,business_number', 'type:id,code,name'])
            ->when($filters['muzaki_id'] ?? null, fn ($q, $v) => $q->where('muzaki_id', $v))
            ->when($filters['zakat_type_id'] ?? null, fn ($q, $v) => $q->where('zakat_type_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['eligibility_status'] ?? null, fn ($q, $v) => $q->where('eligibility_status', $v))
            ->when($filters['calculation_date_from'] ?? null, fn ($q, $v) => $q->whereDate('calculation_date', '>=', $v))
            ->when($filters['calculation_date_to'] ?? null, fn ($q, $v) => $q->whereDate('calculation_date', '<=', $v))
            ->when($filters['created_by'] ?? null, fn ($q, $v) => $q->where('created_by', $v))
            ->orderByDesc('created_at')
            ->paginate(min((int) ($filters['per_page'] ?? 15), (int) config('zakat.pagination.max_per_page')));
    }

    public function create(array $data, bool $calculate = false): ZakatCalculation
    {
        $date = $data['calculation_date'] ?? now()->toDateString();
        $muzaki = Muzaki::find($data['muzaki_id']) ?? throw ZakatException::notFound('Muzaki tidak ditemukan.');
        $type = ZakatType::find($data['zakat_type_id']) ?? throw ZakatException::notFound('Jenis zakat tidak ditemukan.');
        $rule = $this->activeRule($type, $date);

        return DB::transaction(function () use ($data, $date, $muzaki, $type, $rule, $calculate) {
            $calculation = new ZakatCalculation;
            $calculation->fill([
                'calculation_number' => app(BusinessNumberService::class)->next('ZKC'),
                'muzaki_id' => $muzaki->id,
                'zakat_type_id' => $type->id,
                'zakat_rule_id' => $rule->id,
                'rule_version' => $rule->version,
                'calculation_date' => $date,
                'valid_until' => $data['valid_until'] ?? null,
                'currency' => $data['currency'] ?? 'IDR',
            ]);
            $calculation->organization_id = OrganizationContext::requireId();
            $calculation->created_by = auth()->id();
            $calculation->save();
            $this->saveInputs($calculation, $data['inputs'] ?? []);
            $this->audit->record('zakat_calculation_created', $calculation);

            return $calculate ? $this->calculate($calculation) : $this->find($calculation->id);
        });
    }

    public function preview(array $data): array
    {
        $calculation = $this->create($data);
        $result = $this->calculate($calculation)->toArray();
        $calculation->forceDelete();

        return $result;
    }

    public function find(string $id): ZakatCalculation
    {
        return ZakatCalculation::with(['inputs', 'snapshot', 'adjustments', 'versions', 'muzaki', 'type', 'rule'])->find($id)
            ?? throw ZakatException::notFound('Calculation zakat tidak ditemukan.');
    }

    public function calculate(ZakatCalculation $calculation): ZakatCalculation
    {
        if ($calculation->status !== CalculationStatus::Draft) {
            throw ZakatException::invalidTransition('Hanya calculation draft yang dapat dihitung.');
        }
        if ($calculation->valid_until?->isBefore(today())) {
            $calculation->forceFill(['status' => CalculationStatus::Expired])->saveQuietly();
            $this->audit->record('zakat_calculation_expired', $calculation);
            throw ZakatException::invalidTransition('Calculation sudah kedaluwarsa.');
        }

        $calculation->load(['inputs', 'type', 'rule.parameters', 'rule.formulaDefinitions']);
        $inputs = $calculation->inputs->mapWithKeys(fn ($input) => [$input->parameter_code => $input->value['value'] ?? $input->value])->all();
        $rule = $calculation->rule;
        $type = $calculation->type;
        foreach ($rule->parameters as $parameter) {
            if (! array_key_exists($parameter->parameter_code, $inputs) && $parameter->default_value !== null) {
                $inputs[$parameter->parameter_code] = $parameter->default_value;
            }
        }
        $method = $type->calculation_method;
        $formula = $this->formula($rule, $type);
        $missing = $this->missingRequired($rule, $inputs);
        $invalid = $this->invalidInputs($rule, $inputs);
        if ($method === CalculationMethod::Custom && $formula === null) {
            $missing[] = 'FORMULA_DEFINITION';
        }

        $rate = $this->rate($rule, $calculation->calculation_date);
        $nisab = $this->nisab($rule, $calculation->calculation_date);
        $regionId = $inputs['REGION_ID'] ?? null;
        $reference = $nisab?->reference_type ? $this->reference($nisab->reference_type, $calculation->calculation_date, $regionId) : null;
        if (in_array($method, [CalculationMethod::NisabBased, CalculationMethod::AssetBased, CalculationMethod::IncomeBased], true) && $nisab === null) {
            $missing[] = 'NISAB';
        }
        if ($nisab?->reference_type && $reference === null) {
            $missing[] = 'REFERENCE_VALUE_'.$nisab->reference_type;
        }
        if ($method !== CalculationMethod::Fixed && ! in_array($method, [CalculationMethod::HarvestBased, CalculationMethod::LivestockBased], true) && $rate === null) {
            $missing[] = 'RATE';
        }

        $gross = $this->number($inputs['GROSS_AMOUNT'] ?? $inputs['TOTAL_ASSET'] ?? $inputs['GROSS_INCOME'] ?? $inputs['BASE_AMOUNT'] ?? 0);
        $deduction = $this->number($inputs['DEDUCTION'] ?? $inputs['ELIGIBLE_DEDUCTION'] ?? 0);
        $net = max(0, $gross - $deduction);
        $base = match ($method) {
            CalculationMethod::Fixed => $gross,
            CalculationMethod::Percentage => $this->number($inputs['CALCULATION_BASE'] ?? $net),
            CalculationMethod::NisabBased, CalculationMethod::AssetBased, CalculationMethod::IncomeBased => $net,
            CalculationMethod::HarvestBased => $this->number($inputs['HARVEST_QUANTITY'] ?? $inputs['QUANTITY'] ?? 0),
            CalculationMethod::LivestockBased => $this->number($inputs['LIVESTOCK_QUANTITY'] ?? $inputs['QUANTITY'] ?? 0),
            default => $net,
        };
        $nisabAmount = $nisab ? $this->number($nisab->reference_value ?? $nisab->quantity ?? 0) : null;
        if ($reference && $nisab?->quantity) {
            $nisabAmount = $this->multiply((float) $nisab->quantity, (float) $reference->value);
        }
        $haul = $this->haul($rule, $inputs, $calculation->calculation_date);
        $special = $this->specialMethodResult($method, $rule, $inputs, $base);
        $missing = array_values(array_unique(array_merge($missing, $special['missing'])));
        $eligible = $this->eligibility($missing, $invalid, $haul, $nisabAmount, $base, $special['review']);
        $rateValue = $rate ? $this->number($rate->rate_value) : ($special['rate'] ?? 0);
        $zakat = $special['amount'] ?? ($eligible === EligibilityStatus::Eligible ? ($method === CalculationMethod::Fixed ? $base : $this->multiply($base, $rateValue / 100)) : 0);
        if ($eligible !== EligibilityStatus::Eligible) {
            $zakat = 0;
        }
        $result = [
            'summary' => ['calculation_number' => $calculation->calculation_number, 'zakat_amount' => $this->money($zakat), 'currency' => $calculation->currency],
            'breakdown' => ['gross_amount' => $this->money($gross), 'deduction_amount' => $this->money($deduction), 'net_amount' => $this->money($net), 'calculation_base' => $this->money($base), 'nisab_amount' => $nisabAmount === null ? null : $this->money($nisabAmount), 'rate' => $rateValue],
            'eligibility' => $eligible->value,
            'haul' => $haul,
            'inputs' => $inputs,
            'reference_value' => $reference?->only(['reference_code', 'reference_type', 'value', 'unit', 'effective_from', 'region_id']),
            'formula' => ['code' => $formula['code'] ?? null, 'method' => $method->value, 'version' => $formula['version'] ?? $rule->version],
            'warnings' => array_merge($invalid, $missing === [] ? [] : ['INPUT_REQUIRED: '.implode(', ', $missing)]),
        ];

        $calculation->forceFill(['status' => CalculationStatus::Calculated, 'eligibility_status' => $eligible, 'formula_code' => $formula['code'] ?? null, 'formula_version' => $formula['version'] ?? null, 'gross_amount' => $gross, 'deduction_amount' => $deduction, 'net_amount' => $net, 'nisab_amount' => $nisabAmount, 'zakat_rate' => $rateValue, 'zakat_amount' => $zakat, 'result_data' => $result])->save();
        ZakatCalculationSnapshot::create(['calculation_id' => $calculation->id, 'zakat_type_snapshot' => $type->toArray(), 'zakat_rule_snapshot' => $rule->toArray(), 'nisab_snapshot' => $nisab?->toArray(), 'haul_snapshot' => $rule->haul?->toArray(), 'rate_snapshot' => $rate?->toArray(), 'reference_value_snapshot' => $reference?->toArray(), 'parameter_snapshot' => $rule->parameters->toArray(), 'formula_snapshot' => $result['formula'], 'result_snapshot' => $result]);
        $this->audit->record('zakat_calculation_completed', $calculation);

        return $this->find($calculation->id);
    }

    public function confirm(ZakatCalculation $calculation): ZakatCalculation
    {
        if ($calculation->status !== CalculationStatus::Calculated) {
            throw ZakatException::invalidTransition('Hanya calculation calculated yang dapat dikonfirmasi.');
        }
        $calculation->forceFill(['status' => CalculationStatus::Confirmed])->saveQuietly();
        $this->audit->record('zakat_calculation_confirmed', $calculation);

        return $calculation;
    }

    public function cancel(ZakatCalculation $calculation, string $reason): ZakatCalculation
    {
        if (in_array($calculation->status, [CalculationStatus::Converted, CalculationStatus::Cancelled], true)) {
            throw ZakatException::invalidTransition('Calculation tidak dapat dibatalkan.');
        }
        $calculation->forceFill(['status' => CalculationStatus::Cancelled])->saveQuietly();
        $this->audit->record('zakat_calculation_cancelled', $calculation, context: ['reason' => $reason]);

        return $calculation;
    }

    public function recalculate(ZakatCalculation $calculation, array $data): ZakatCalculation
    {
        if (! in_array($calculation->status, [CalculationStatus::Calculated, CalculationStatus::Confirmed, CalculationStatus::Expired], true)) {
            throw ZakatException::invalidTransition('Calculation ini belum dapat direcalculate.');
        }
        $calculation->load('inputs');
        $inputs = collect($calculation->inputs)->mapWithKeys(fn ($input) => [$input->parameter_code => $input->value])->all();
        $new = $this->create(array_merge([
            'muzaki_id' => $calculation->muzaki_id,
            'zakat_type_id' => $calculation->zakat_type_id,
            'calculation_date' => $calculation->calculation_date?->toDateString(),
            'currency' => $calculation->currency,
            'inputs' => $inputs,
        ], $data), true);
        $new->forceFill(['parent_calculation_id' => $calculation->id])->saveQuietly();
        $version = (int) ZakatCalculationVersion::where('parent_calculation_id', $calculation->id)->max('version') + 1;
        ZakatCalculationVersion::create(['calculation_id' => $new->id, 'version' => $version, 'parent_calculation_id' => $calculation->id, 'reason' => $data['reason'] ?? 'Recalculation', 'created_by' => auth()->id()]);
        $this->audit->record('zakat_calculation_recalculated', $new, context: ['parent_calculation_id' => $calculation->id]);

        return $this->find($new->id);
    }

    public function adjust(ZakatCalculation $calculation, array $data): ZakatCalculation
    {
        if (! in_array($calculation->status, [CalculationStatus::Calculated, CalculationStatus::Confirmed], true)) {
            throw ZakatException::invalidTransition('Calculation belum siap disesuaikan.');
        }
        $original = (float) $calculation->zakat_amount;
        $amount = (float) $data['adjustment_amount'];
        $final = match ($data['adjustment_type']) {
            'increase' => $original + $amount,
            'decrease' => max(0, $original - $amount),
            'override' => $amount,
        };
        ZakatCalculationAdjustment::create(['calculation_id' => $calculation->id, 'adjustment_type' => $data['adjustment_type'], 'original_amount' => $original, 'adjustment_amount' => $amount, 'final_amount' => $final, 'reason' => $data['reason'], 'created_by' => auth()->id()]);
        $calculation->forceFill(['zakat_amount' => $final])->saveQuietly();
        $this->audit->record('zakat_calculation_adjusted', $calculation, context: ['reason' => $data['reason']]);

        return $this->find($calculation->id);
    }

    public function convert(ZakatCalculation $calculation): never
    {
        if ($calculation->status !== CalculationStatus::Confirmed) {
            throw ZakatException::invalidTransition('Hanya calculation confirmed yang dapat dikonversi.');
        }
        throw ZakatException::conflict('Collection module belum tersedia untuk conversion.');
    }

    private function activeRule(ZakatType $type, string $date): ZakatRule
    {
        $rules = ZakatRule::with('parameters')->where('zakat_type_id', $type->id)->where('status', ZakatStatus::Active)->whereDate('effective_from', '<=', $date)->where(fn ($q) => $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', $date))->get();
        if ($rules->count() !== 1) {
            throw ZakatException::conflict($rules->isEmpty() ? 'Tidak ada rule aktif untuk jenis zakat dan tanggal tersebut.' : 'Rule aktif konflik untuk jenis zakat dan tanggal tersebut.');
        }

        return $rules->first();
    }

    private function saveInputs(ZakatCalculation $calculation, array $inputs): void
    {
        foreach ($inputs as $code => $value) {
            $value = is_array($value) ? $value : ['value' => $value];
            ZakatCalculationInput::updateOrCreate(['calculation_id' => $calculation->id, 'parameter_code' => $code], ['value' => $value, 'normalized_value' => $value['value'] ?? null, 'unit' => $value['unit'] ?? null, 'currency' => $value['currency'] ?? null, 'source' => $value['source'] ?? 'manual']);
        }
    }

    private function formula(ZakatRule $rule, ZakatType $type): ?array
    {
        $definition = $rule->formulaDefinitions->where('status', 'active')->sortByDesc('formula_version')->first();
        if ($definition) {
            return ['code' => $definition->formula_code, 'version' => $definition->formula_version];
        }
        if ($type->calculation_method !== CalculationMethod::Custom) {
            return ['code' => strtoupper($type->code.$type->calculation_method->value), 'version' => $rule->version];
        }

        return null;
    }

    private function missingRequired(ZakatRule $rule, array $inputs): array
    {
        return $rule->parameters->where('is_required', true)->filter(fn ($p) => ! array_key_exists($p->parameter_code, $inputs) || $inputs[$p->parameter_code] === null || $inputs[$p->parameter_code] === '')->pluck('parameter_code')->values()->all();
    }

    private function invalidInputs(ZakatRule $rule, array $inputs): array
    {
        $errors = [];
        foreach ($rule->parameters as $parameter) {
            if (! array_key_exists($parameter->parameter_code, $inputs)) {
                continue;
            }
            $value = $inputs[$parameter->parameter_code];
            $valid = match ($parameter->data_type) {
                'number', 'decimal', 'integer' => is_numeric($value),
                'boolean' => is_bool($value) || in_array($value, [0, 1, '0', '1'], true),
                'date' => is_string($value) && Carbon::canBeCreatedFromFormat($value, 'Y-m-d'),
                default => is_scalar($value),
            };
            if (! $valid) {
                $errors[] = 'INVALID_'.$parameter->parameter_code;
            }
        }

        return $errors;
    }

    private function rate(ZakatRule $rule, string $date)
    {
        return $rule->rates()->whereDate('effective_from', '<=', $date)->where(fn ($q) => $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', $date))->orderByDesc('effective_from')->first();
    }

    private function nisab(ZakatRule $rule, string $date)
    {
        return $rule->nisab()->whereDate('effective_from', '<=', $date)->where(fn ($q) => $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', $date))->orderByDesc('effective_from')->first();
    }

    private function reference(string $type, string $date, ?string $regionId)
    {
        $organizationId = OrganizationContext::requireId();

        return ZakatReferenceValue::withoutGlobalScope(OrganizationScope::class)
            ->where(fn ($q) => $q->where('organization_id', $organizationId)->orWhereNull('organization_id'))
            ->where('reference_type', $type)
            ->where('status', 'active')
            ->whereDate('effective_from', '<=', $date)
            ->where(fn ($q) => $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', $date))
            ->orderByRaw('CASE WHEN organization_id = ? AND region_id = ? THEN 0 WHEN organization_id = ? AND region_id IS NULL THEN 1 WHEN organization_id IS NULL AND region_id = ? THEN 2 ELSE 3 END', [$organizationId, $regionId, $organizationId, $regionId])
            ->orderByDesc('effective_from')
            ->first();
    }

    private function haul(ZakatRule $rule, array $inputs, string $date): array
    {
        $haul = $rule->haul;
        if (! $haul || $haul->haul_type === 'not_required') {
            return ['status' => 'haul_not_required'];
        }
        if (! isset($inputs['OWNERSHIP_START_DATE'])) {
            return ['status' => 'haul_unknown'];
        }
        $start = Carbon::parse($inputs['OWNERSHIP_START_DATE']);
        $end = Carbon::parse($date);
        $months = match ($haul->duration_unit) {
            'day', 'days' => $start->diffInDays($end) / 30,
            'year', 'years' => $start->diffInYears($end) * 12,
            default => $start->diffInMonths($end),
        };
        $required = ($haul->duration ?? 12) * (in_array($haul->duration_unit, ['year', 'years'], true) ? 12 : 1);

        return ['status' => $months >= $required ? 'haul_met' : 'haul_not_met', 'months' => $months, 'required_months' => $required];
    }

    private function specialMethodResult(CalculationMethod $method, ZakatRule $rule, array $inputs, float $base): array
    {
        if ($method === CalculationMethod::HarvestBased) {
            $config = ZakatAgricultureConfiguration::where('zakat_rule_id', $rule->id)->when($inputs['COMMODITY'] ?? null, fn ($q, $v) => $q->where('commodity_type', $v))->when($inputs['IRRIGATION_TYPE'] ?? null, fn ($q, $v) => $q->where('irrigation_type', $v))->first();
            if (! $config) {
                return ['missing' => ['AGRICULTURE_CONFIGURATION'], 'review' => true];
            }
            if ($base < (float) $config->minimum_quantity) {
                return ['missing' => [], 'review' => false, 'amount' => 0, 'rate' => (float) $config->rate];
            }

            return ['missing' => [], 'review' => false, 'amount' => $this->multiply($base, (float) $config->rate / 100), 'rate' => (float) $config->rate];
        }
        if ($method === CalculationMethod::LivestockBased) {
            $config = ZakatLivestockConfiguration::where('zakat_rule_id', $rule->id)->where('livestock_type', $inputs['LIVESTOCK_TYPE'] ?? '')->where('minimum_quantity', '<=', $base)->where(fn ($q) => $q->whereNull('maximum_quantity')->orWhere('maximum_quantity', '>=', $base))->first();
            if (! $config) {
                return ['missing' => [], 'review' => true];
            }

            return ['missing' => [], 'review' => false, 'amount' => (float) $config->zakat_quantity];
        }

        return ['missing' => [], 'review' => false];
    }

    private function eligibility(array $missing, array $invalid, array $haul, ?float $nisab, float $base, bool $review): EligibilityStatus
    {
        if ($invalid !== [] || $missing !== []) {
            return EligibilityStatus::Incomplete;
        }
        if ($review || $haul['status'] === 'haul_unknown') {
            return EligibilityStatus::ReviewRequired;
        }
        if ($haul['status'] === 'haul_not_met' || ($nisab !== null && $base < $nisab)) {
            return EligibilityStatus::NotEligible;
        }

        return EligibilityStatus::Eligible;
    }

    private function expireDueCalculations(): void
    {
        ZakatCalculation::whereIn('status', [CalculationStatus::Draft->value, CalculationStatus::Calculated->value, CalculationStatus::Confirmed->value])->whereDate('valid_until', '<', today())->update(['status' => CalculationStatus::Expired->value, 'updated_at' => now()]);
    }

    private function number(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function multiply(float $a, float $b): float
    {
        return (float) bcmul((string) $a, (string) $b, 8);
    }

    private function money(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
