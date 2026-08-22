<?php

namespace App\Services;

use App\Enums\EntityStatus;
use App\Enums\ReportCategory;
use App\Enums\ReportVisibility;
use App\Exceptions\ZakatException;
use App\Models\Report;
use App\Models\UserReportFavorite;
use App\Support\OrganizationContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/** PRD 19C dan 19M — katalog laporan beserta kontrol aksesnya. */
class ReportService
{
    public function __construct(private readonly AuditService $audits) {}

    public function list(array $filters): LengthAwarePaginator
    {
        return Report::query()
            ->visible()
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(
                fn ($inner) => $inner->where('name', 'ilike', "%{$search}%")->orWhere('report_code', 'ilike', "%{$search}%")
            ))
            ->orderBy('category')
            ->orderBy('name')
            ->paginate(min((int) ($filters['per_page'] ?? config('zakat.pagination.per_page')), config('zakat.pagination.max_per_page')));
    }

    public function find(string $id): Report
    {
        $report = Report::query()->visible()->with('parameters')->whereKey($id)->first();

        return $report ?? throw ZakatException::notFound('Laporan tidak ditemukan.');
    }

    /**
     * PRD 19M §37 dan PRD 19N §39 — akses ditentukan kategori dan visibility,
     * bukan oleh route saja, karena satu endpoint melayani semua laporan.
     */
    public function assertAccessible(Report $report): void
    {
        $user = Auth::user();
        $organizationId = OrganizationContext::id();

        foreach (array_filter([$report->category->permission(), $report->visibility->extraPermission()]) as $permission) {
            if ($user?->hasPermissionTo($permission, $organizationId) !== true) {
                throw ZakatException::forbidden("Laporan ini memerlukan izin {$permission}.");
            }
        }

        // PRD 19W §23 — laporan milik organisasi lain tidak boleh terbaca.
        if ($report->organization_id !== null && $report->organization_id !== $organizationId) {
            throw ZakatException::notFound('Laporan tidak ditemukan.');
        }
    }

    public function create(array $data): Report
    {
        $data['report_code'] = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $data['report_code']) ?? '');

        $report = new Report;
        $report->fill($data);
        $report->organization_id = OrganizationContext::requireId();
        $report->created_by = Auth::id();
        $report->status = EntityStatus::Active;
        $report->visibility = ReportVisibility::from($data['visibility'] ?? ReportVisibility::Internal->value);
        $report->category = ReportCategory::from($data['category'] ?? ReportCategory::Custom->value);
        $report->is_system = false;
        $report->save();

        $this->audits->record('report_created', $report, after: $report->getAttributes());

        return $report;
    }

    public function update(Report $report, array $data): Report
    {
        $this->assertEditable($report);

        $before = $report->getOriginal();
        $report->fill($data);
        $report->save();

        $this->audits->record('report_updated', $report, $before, $report->getAttributes());

        return $report;
    }

    public function setStatus(Report $report, EntityStatus $status): Report
    {
        $this->assertEditable($report);

        $report->status = $status;
        $report->save();

        $this->audits->record($status === EntityStatus::Active ? 'report_activated' : 'report_deactivated', $report);

        return $report;
    }

    /** PRD 19P §41. */
    public function favorites(): Collection
    {
        $ids = UserReportFavorite::query()->where('user_id', Auth::id())->pluck('report_id');

        return Report::query()->visible()->whereIn('id', $ids)->orderBy('name')->get();
    }

    public function addFavorite(Report $report): void
    {
        UserReportFavorite::query()->updateOrCreate(
            ['user_id' => Auth::id(), 'report_id' => $report->getKey()],
            ['created_at' => now()],
        );
    }

    public function removeFavorite(Report $report): void
    {
        UserReportFavorite::query()->where('user_id', Auth::id())->where('report_id', $report->getKey())->delete();
    }

    /** Laporan bawaan sistem tidak boleh diubah organisasi mana pun. */
    private function assertEditable(Report $report): void
    {
        if ($report->is_system) {
            throw ZakatException::forbidden('Laporan bawaan sistem tidak dapat diubah.');
        }

        if ($report->organization_id !== OrganizationContext::id()) {
            throw ZakatException::notFound('Laporan tidak ditemukan.');
        }
    }
}
