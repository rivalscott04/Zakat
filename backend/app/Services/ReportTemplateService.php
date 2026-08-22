<?php

namespace App\Services;

use App\Enums\EntityStatus;
use App\Models\ReportTemplate;
use App\Support\OrganizationContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

/** PRD 19J §29 — template menentukan judul, kolom, urutan, dan ringkasan laporan. */
class ReportTemplateService
{
    public function __construct(private readonly AuditService $audits) {}

    public function list(array $filters): LengthAwarePaginator
    {
        return ReportTemplate::query()
            ->with('report')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('template_code')
            ->paginate(min((int) ($filters['per_page'] ?? config('zakat.pagination.per_page')), config('zakat.pagination.max_per_page')));
    }

    public function find(string $id): ReportTemplate
    {
        return ReportTemplate::query()->with('report')->findOrFail($id);
    }

    public function create(array $data): ReportTemplate
    {
        $data['template_code'] = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $data['template_code']) ?? '');

        $template = new ReportTemplate;
        $template->fill($data);
        $template->organization_id = OrganizationContext::requireId();
        $template->created_by = Auth::id();
        $template->status = EntityStatus::Draft;
        $template->save();

        $this->audits->record('report_template_created', $template, after: $template->getAttributes());

        return $template->load('report');
    }

    public function update(ReportTemplate $template, array $data): ReportTemplate
    {
        $before = $template->getOriginal();

        if (isset($data['template_code'])) {
            $data['template_code'] = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $data['template_code']) ?? '');
        }

        $template->fill($data);
        $template->save();

        $this->audits->record('report_template_updated', $template, $before, $template->getAttributes());

        return $template->load('report');
    }

    public function setStatus(ReportTemplate $template, EntityStatus $status): ReportTemplate
    {
        $template->status = $status;
        $template->save();

        $this->audits->record(
            $status === EntityStatus::Active ? 'report_template_activated' : 'report_template_deactivated',
            $template,
        );

        return $template->load('report');
    }
}
