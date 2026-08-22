<?php

namespace App\Services;

use App\Enums\ReportRunStatus;
use App\Exceptions\ZakatException;
use App\Jobs\GenerateReportRun;
use App\Models\Report;
use App\Models\ReportParameter;
use App\Models\ReportRun;
use App\Reports\ReportRegistry;
use App\Reports\StandardReportQueries;
use App\Support\OrganizationContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * PRD 19G §22 dan PRD 19H — menjalankan laporan lalu menyimpan snapshotnya.
 *
 * Snapshot dibuat supaya laporan lama tidak ikut berubah ketika data sumbernya
 * berubah (PRD 19B §4 dan PRD 19W §14).
 */
class ReportRunService
{
    /** PRD 19V §57 — di atas ambang ini laporan tidak dijalankan dalam request utama. */
    private const INLINE_ROW_ESTIMATE = 5000;

    public function __construct(
        private readonly AuditService $audits,
        private readonly StandardReportQueries $queries,
    ) {}

    public function list(array $filters): LengthAwarePaginator
    {
        return ReportRun::query()
            ->with('report')
            ->when($filters['report_id'] ?? null, fn ($query, $id) => $query->where('report_id', $id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('created_at')
            ->paginate(min((int) ($filters['per_page'] ?? config('zakat.pagination.per_page')), config('zakat.pagination.max_per_page')));
    }

    public function find(string $id): ReportRun
    {
        return ReportRun::query()->with(['report', 'exports'])->findOrFail($id);
    }

    /**
     * PRD 19G §22 — parameter divalidasi sebelum data dimuat.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function run(Report $report, array $parameters, bool $forceQueue = false): ReportRun
    {
        $parameters = $this->validateParameters($report, $parameters);

        $run = new ReportRun;
        $run->organization_id = OrganizationContext::requireId();
        $run->report_id = $report->getKey();
        $run->parameters = $parameters;
        $run->status = ReportRunStatus::Queued;
        $run->generated_by = Auth::id();
        $run->generated_at = now();
        $run->save();

        $this->audits->record('report_run_created', $run, after: $run->only(['run_number', 'report_id', 'parameters']));

        if ($forceQueue || $this->isLarge($report, $parameters)) {
            GenerateReportRun::dispatch($run->getKey());

            return $run;
        }

        return $this->generate($run);
    }

    /** Dipanggil langsung maupun oleh job. Tidak melempar: kegagalan tercatat pada run. */
    public function generate(ReportRun $run): ReportRun
    {
        if ($run->status->isFinal()) {
            return $run;
        }

        $run->status = ReportRunStatus::Processing;
        $run->save();

        try {
            $report = $run->report;

            $result = ReportRegistry::has($report->report_code)
                ? $this->queries->run($report->report_code, $run->organization_id, $run->parameters ?? [])
                : throw ZakatException::conflict("Laporan [{$report->report_code}] belum memiliki sumber data.");

            $run->snapshot_data = $result;
            $run->row_count = count($result['rows']);
            $run->status = ReportRunStatus::Completed;
            $run->completed_at = now();
            $run->error_message = null;
            $run->save();

            $this->audits->record('report_run_completed', $run, after: ['row_count' => $run->row_count]);
        } catch (Throwable $exception) {
            // PRD 19V §58 — kegagalan dicatat dan dapat diulang, bukan menghilang.
            $run->status = ReportRunStatus::Failed;
            $run->failed_at = now();
            $run->error_message = substr($exception->getMessage(), 0, 1000);
            $run->save();

            $this->audits->record('report_run_failed', $run, context: ['error' => $run->error_message]);
        }

        return $run;
    }

    /** PRD 19W §13 — laporan gagal dapat diulang tanpa kehilangan riwayatnya. */
    public function retry(ReportRun $run): ReportRun
    {
        if ($run->status !== ReportRunStatus::Failed) {
            throw ZakatException::invalidTransition('Hanya report run yang gagal yang dapat diulang.');
        }

        $run->status = ReportRunStatus::Queued;
        $run->failed_at = null;
        $run->error_message = null;
        $run->save();

        return $this->generate($run);
    }

    public function cancel(ReportRun $run): ReportRun
    {
        if ($run->status->isFinal()) {
            throw ZakatException::invalidTransition('Report run yang sudah selesai tidak dapat dibatalkan.');
        }

        $run->status = ReportRunStatus::Cancelled;
        $run->save();

        $this->audits->record('report_run_cancelled', $run);

        return $run;
    }

    /**
     * PRD 19W §9 dan §10 — parameter wajib tidak boleh kosong dan tipenya diperiksa.
     *
     * @return array<string, mixed>
     */
    public function validateParameters(Report $report, array $parameters): array
    {
        $definitions = $report->parameters()->get();

        if ($definitions->isEmpty()) {
            return [];
        }

        $rules = [];
        $labels = [];

        foreach ($definitions as $definition) {
            /** @var ReportParameter $definition */
            $rules[$definition->parameter_code] = array_merge(
                [$definition->required ? 'required' : 'nullable'],
                $definition->type->rules(),
            );
            $labels[$definition->parameter_code] = $definition->label;
        }

        $defaults = $definitions
            ->filter(fn (ReportParameter $definition) => $definition->default_value !== null)
            ->mapWithKeys(fn (ReportParameter $definition) => [$definition->parameter_code => $definition->default_value])
            ->all();

        $validator = Validator::make(
            array_filter($parameters, fn ($value) => $value !== null && $value !== '') + $defaults,
            $rules,
            [],
            $labels,
        );

        return $validator->validate();
    }

    /**
     * Perkiraan kasar besar laporan. Cukup memakai jumlah baris tabel sumber:
     * yang penting laporan besar tidak dikerjakan di request utama.
     */
    private function isLarge(Report $report, array $parameters): bool
    {
        $source = $report->data_source;

        if ($source === null || $source === 'multiple'
            || ! Schema::hasTable($source)
            || ! Schema::hasColumn($source, 'organization_id')) {
            return false;
        }

        return DB::table($source)
            ->where('organization_id', OrganizationContext::requireId())
            ->count() > self::INLINE_ROW_ESTIMATE;
    }
}
