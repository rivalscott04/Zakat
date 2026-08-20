<?php

namespace App\Services;

use App\Enums\ZakatStatus;
use App\Exceptions\ZakatException;
use App\Models\ZakatCategory;
use App\Models\ZakatRule;
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
        return ZakatRule::with('type')->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->when($filters['zakat_type_id'] ?? null, fn ($q, $v) => $q->where('zakat_type_id', $v))->orderByDesc('effective_from')->paginate(min((int) ($filters['per_page'] ?? 15), (int) config('zakat.pagination.max_per_page')));
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
            $this->audit->record('zakat_rule_created', $m);

            return $m->load('type');
        });
    }

    public function changeRuleStatus(ZakatRule $rule, ZakatStatus $status): ZakatRule
    {
        if ($rule->status === ZakatStatus::Archived) {
            throw ZakatException::invalidTransition('Rule archived tidak dapat diubah.');
        } $old = $rule->status;
        $rule->status = $status;
        $rule->saveQuietly();
        $this->audit->record('zakat_rule_status_changed', $rule, ['status' => $old->value], ['status' => $status->value]);

        return $rule;
    }
}
