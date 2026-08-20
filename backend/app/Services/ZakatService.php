<?php

namespace App\Services;

use App\Enums\ZakatStatus;
use App\Exceptions\ZakatException;
use App\Models\ZakatAgricultureConfiguration;
use App\Models\ZakatCategory;
use App\Models\ZakatFitrahConfiguration;
use App\Models\ZakatFormulaDefinition;
use App\Models\ZakatHaul;
use App\Models\ZakatLivestockConfiguration;
use App\Models\ZakatNisab;
use App\Models\ZakatRate;
use App\Models\ZakatReferenceValue;
use App\Models\ZakatRule;
use App\Models\ZakatRuleParameter;
use App\Models\ZakatType;
use App\Support\OrganizationContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ZakatService
{
    public function __construct(private readonly AuditService $audit) {}

    public function categories(array $filters): LengthAwarePaginator
    {
        return ZakatCategory::with('types')->orderBy('sort_order')->orderBy('name')->paginate(min((int) ($filters['per_page'] ?? 15), (int) config('zakat.pagination.max_per_page')));
    }

    public function types(array $filters): LengthAwarePaginator
    {
        return ZakatType::with('category')->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($x) => $x->where('name', 'ilike', "%{$s}%")->orWhere('code', 'ilike', "%{$s}%")))->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->orderBy('sort_order')->paginate(min((int) ($filters['per_page'] ?? 15), (int) config('zakat.pagination.max_per_page')));
    }

    public function rules(array $filters): LengthAwarePaginator
    {
        return ZakatRule::with(['type', 'parameters'])->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->when($filters['zakat_type_id'] ?? null, fn ($q, $v) => $q->where('zakat_type_id', $v))->orderByDesc('effective_from')->paginate(min((int) ($filters['per_page'] ?? 15), (int) config('zakat.pagination.max_per_page')));
    }

    public function createCategory(array $data): ZakatCategory
    {
        $m = new ZakatCategory;
        $m->fill($data);
        $m->organization_id = OrganizationContext::requireId();
        $m->save();
        $this->audit->record('zakat_category_created', $m);

        return $m;
    }

    public function createType(array $data): ZakatType
    {
        ZakatCategory::find($data['zakat_category_id']) ?? throw ZakatException::notFound('Kategori zakat tidak ditemukan.');

        $m = new ZakatType;
        $m->fill($data);
        $m->organization_id = OrganizationContext::requireId();
        $m->save();
        $this->audit->record('zakat_type_created', $m);

        return $m->load('category');
    }

    public function createRule(array $data): ZakatRule
    {
        return DB::transaction(function () use ($data) {
            $type = ZakatType::find($data['zakat_type_id']) ?? throw ZakatException::notFound('Jenis zakat tidak ditemukan.');
            $m = new ZakatRule;
            $m->fill($data);
            $m->organization_id = OrganizationContext::requireId();
            $m->rule_code = $type->code.date('Y', strtotime($data['effective_from'])).'V'.$data['version'];
            $m->status = ZakatStatus::Draft;
            $m->save();
            ZakatFormulaDefinition::firstOrCreate(
                ['zakat_rule_id' => $m->id, 'formula_code' => strtoupper($type->code.$type->calculation_method->value), 'formula_version' => $m->version],
                ['formula_type' => $type->calculation_method->value, 'expression' => 'predefined', 'input_schema' => [], 'output_schema' => ['zakat_amount' => 'decimal'], 'status' => 'active']
            );
            $this->audit->record('zakat_rule_created', $m);

            return $m->load('type');
        });
    }

    public function changeRuleStatus(ZakatRule $rule, ZakatStatus $status): ZakatRule
    {
        if ($rule->status === ZakatStatus::Archived && $status !== ZakatStatus::Archived) {
            throw ZakatException::invalidTransition('Rule archived tidak dapat diubah.');
        }

        if ($status === ZakatStatus::Active) {
            $this->assertNoActiveRuleOverlap($rule);
        }

        $old = $rule->status;
        $rule->status = $status;
        $rule->saveQuietly();
        $this->audit->record('zakat_rule_status_changed', $rule, ['status' => $old->value], ['status' => $status->value]);

        return $rule;
    }

    private function assertNoActiveRuleOverlap(ZakatRule $rule): void
    {
        $conflict = ZakatRule::query()
            ->where('zakat_type_id', $rule->zakat_type_id)
            ->where('status', ZakatStatus::Active)
            ->where($rule->getKeyName(), '<>', $rule->getKey())
            ->whereDate('effective_from', '<=', $rule->effective_until?->toDateString() ?? '9999-12-31')
            ->where(fn ($q) => $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', $rule->effective_from))
            ->exists();

        if ($conflict) {
            throw ZakatException::conflict('Periode rule aktif bertabrakan untuk jenis zakat ini.');
        }
    }

    public function rule(string $id): ZakatRule
    {
        return ZakatRule::with(['type', 'rates', 'nisab', 'haul', 'parameters', 'formulaDefinitions'])->find($id) ?? throw ZakatException::notFound('Rule zakat tidak ditemukan.');
    }

    public function config(string $ruleId, string $kind, array $data): mixed
    {
        $rule = $this->rule($ruleId);
        $class = match ($kind) {
            'rate' => ZakatRate::class, 'nisab' => ZakatNisab::class, 'haul' => ZakatHaul::class, 'parameter' => ZakatRuleParameter::class,
            'formula' => ZakatFormulaDefinition::class,
            'fitrah' => ZakatFitrahConfiguration::class, 'agriculture' => ZakatAgricultureConfiguration::class, 'livestock' => ZakatLivestockConfiguration::class,
            default => throw ZakatException::conflict('Jenis konfigurasi tidak dikenal.'),
        };
        $model = new $class;
        $model->fill($data);
        $model->zakat_rule_id = $rule->id;
        $model->save();
        $this->audit->record('zakat_'.$kind.'_created', $model);

        return $model;
    }

    public function reference(array $data): ZakatReferenceValue
    {
        $model = new ZakatReferenceValue;
        $model->fill($data);
        $model->organization_id = OrganizationContext::requireId();
        $model->effective_at ??= now();
        $model->save();
        $this->audit->record('zakat_reference_value_created', $model);

        return $model;
    }

    public function resolve(array $filters): array
    {
        $date = $filters['calculation_date'] ?? now()->toDateString();
        $rules = ZakatRule::with(['rates', 'nisab', 'haul', 'parameters', 'type'])->where('status', ZakatStatus::Active)->whereHas('type', fn ($q) => $q->where('code', $filters['zakat_type']))->whereDate('effective_from', '<=', $date)->where(fn ($q) => $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', $date))->get();
        if ($rules->count() !== 1) {
            throw ZakatException::conflict($rules->isEmpty() ? 'Tidak ada rule aktif untuk context tersebut.' : 'Rule aktif konflik untuk context tersebut.');
        }
        $rule = $rules->first();

        return ['resolved_rule' => $rule, 'nisab' => $rule->nisab, 'haul' => $rule->haul, 'rates' => $rule->rates, 'parameters' => $rule->parameters];
    }
}
