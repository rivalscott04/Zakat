<?php

namespace App\Services;

use App\Enums\ReportExportFormat;
use App\Enums\ReportRunStatus;
use App\Exceptions\ZakatException;
use App\Models\ReportExport;
use App\Models\ReportRun;
use App\Reports\ReportFileWriter;
use App\Support\OrganizationContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** PRD 19I — ekspor snapshot ke berkas beserta pengendalian unduhannya. */
class ReportExportService
{
    public const DISK = 'private';

    public function __construct(
        private readonly AuditService $audits,
        private readonly ReportFileWriter $writer,
    ) {}

    public function export(ReportRun $run, ReportExportFormat $format): ReportExport
    {
        // PRD 19I §27 — hanya run yang sudah selesai yang punya isi untuk diekspor.
        if ($run->status !== ReportRunStatus::Completed) {
            throw ZakatException::invalidTransition('Hanya report run selesai yang dapat diekspor.');
        }

        $existing = $run->exports()->where('format', $format)->first();

        if ($existing !== null && ! $existing->isExpired() && Storage::disk(self::DISK)->exists($existing->file_path)) {
            return $existing;
        }

        $snapshot = $run->snapshot_data ?? ['columns' => [], 'rows' => []];

        $content = $this->writer->write(
            $format,
            $run->report->name.' '.$run->run_number,
            $snapshot['columns'] ?? [],
            $snapshot['rows'] ?? [],
        );

        $path = 'reports/'.$run->organization_id.'/'.$run->run_number.'-'.Str::lower(Str::random(8)).'.'.$format->extension();
        Storage::disk(self::DISK)->put($path, $content);

        $export = $existing ?? new ReportExport;
        $export->organization_id = $run->organization_id;
        $export->report_run_id = $run->getKey();
        $export->format = $format;
        $export->file_path = $path;
        $export->file_size = strlen($content);
        $export->expires_at = now()->addDays((int) config('zakat.reporting.export_expires_days', 7));
        $export->created_by = Auth::id();
        $export->save();

        $this->audits->record('report_exported', $run, context: ['format' => $format->value, 'export_id' => $export->getKey()]);

        return $export;
    }

    public function find(string $id): ReportExport
    {
        return ReportExport::query()->with('run.report')->findOrFail($id);
    }

    /**
     * PRD 19W §17 — setiap unduhan dicatat pada audit trail.
     *
     * @return array{path: string, name: string, mime: string}
     */
    public function download(ReportExport $export): array
    {
        if ($export->organization_id !== OrganizationContext::id()) {
            throw ZakatException::notFound('Berkas ekspor tidak ditemukan.');
        }

        if ($export->isExpired()) {
            throw ZakatException::conflict('Tautan unduhan sudah kedaluwarsa. Jalankan ekspor ulang.');
        }

        if (! Storage::disk(self::DISK)->exists($export->file_path)) {
            throw ZakatException::notFound('Berkas ekspor tidak ditemukan.');
        }

        $export->downloaded_at = now();
        $export->download_count = $export->download_count + 1;
        $export->save();

        $this->audits->record(
            'report_downloaded',
            $export->run,
            context: ['format' => $export->format->value, 'export_id' => $export->getKey()],
        );

        return [
            'path' => Storage::disk(self::DISK)->path($export->file_path),
            'name' => $export->run->run_number.'.'.$export->format->extension(),
            'mime' => $export->format->mimeType(),
        ];
    }
}
