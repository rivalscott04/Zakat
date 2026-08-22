<?php

namespace App\Services;

use App\Enums\EntityStatus;
use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\ReportExportFormat;
use App\Enums\ReportFrequency;
use App\Enums\ReportRunStatus;
use App\Models\Report;
use App\Models\ReportSchedule;
use App\Support\OrganizationContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

/** PRD 19K dan 19L — penjadwalan laporan beserta pengirimannya. */
class ReportScheduleService
{
    public function __construct(
        private readonly AuditService $audits,
        private readonly ReportRunService $runs,
        private readonly ReportExportService $exports,
        private readonly NotificationService $notifications,
    ) {}

    public function list(array $filters): LengthAwarePaginator
    {
        return ReportSchedule::query()
            ->with('report')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('next_run_at')
            ->paginate(min((int) ($filters['per_page'] ?? config('zakat.pagination.per_page')), config('zakat.pagination.max_per_page')));
    }

    public function find(string $id): ReportSchedule
    {
        return ReportSchedule::query()->with('report')->findOrFail($id);
    }

    public function create(array $data): ReportSchedule
    {
        $schedule = new ReportSchedule;
        $schedule->fill($data);
        $schedule->organization_id = OrganizationContext::requireId();
        $schedule->created_by = Auth::id();
        $schedule->status = EntityStatus::Active;
        $schedule->output_format = ReportExportFormat::from($data['output_format'] ?? ReportExportFormat::Csv->value);
        $schedule->next_run_at = ReportFrequency::from($data['frequency'])->next(now());
        $schedule->save();

        $this->audits->record('report_schedule_created', $schedule, after: $schedule->getAttributes());

        return $schedule->load('report');
    }

    public function update(ReportSchedule $schedule, array $data): ReportSchedule
    {
        $before = $schedule->getOriginal();

        $schedule->fill($data);

        if (isset($data['frequency'])) {
            $schedule->next_run_at = ReportFrequency::from($data['frequency'])->next(now());
        }

        $schedule->save();

        $this->audits->record('report_schedule_updated', $schedule, $before, $schedule->getAttributes());

        return $schedule->load('report');
    }

    public function setStatus(ReportSchedule $schedule, EntityStatus $status): ReportSchedule
    {
        $schedule->status = $status;
        $schedule->save();

        $this->audits->record(
            $status === EntityStatus::Active ? 'report_schedule_activated' : 'report_schedule_deactivated',
            $schedule,
        );

        return $schedule->load('report');
    }

    /**
     * PRD 19K §32 — generate, export, lalu kirim.
     *
     * PRD 19W §20 mewajibkan pengirimannya lewat modul notification, bukan
     * mailer sendiri.
     */
    public function runNow(ReportSchedule $schedule): ReportSchedule
    {
        $report = Report::query()->findOrFail($schedule->report_id);

        $run = $this->runs->run($report, $schedule->parameters ?? []);

        if ($run->status === ReportRunStatus::Completed) {
            $export = $this->exports->export($run, $schedule->output_format);
            $this->notifyRecipients($schedule, $run->run_number, $export->getKey());
        }

        $schedule->last_run_at = now();
        $schedule->next_run_at = $schedule->frequency->next(now());
        $schedule->save();

        $this->audits->record('report_schedule_executed', $schedule, context: [
            'run_number' => $run->run_number,
            'status' => $run->status->value,
        ]);

        return $schedule->load('report');
    }

    /** PRD 19K — dijalankan penjadwal untuk seluruh organisasi. */
    public function runDue(): int
    {
        $due = ReportSchedule::query()
            ->acrossOrganizations()
            ->where('status', EntityStatus::Active)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->get();

        foreach ($due as $schedule) {
            $this->runNow($schedule);
        }

        return $due->count();
    }

    private function notifyRecipients(ReportSchedule $schedule, string $runNumber, string $exportId): void
    {
        $recipients = (array) ($schedule->recipient_configuration['user_ids'] ?? []);
        $channels = array_map(
            fn (string $channel) => NotificationChannel::from($channel),
            (array) ($schedule->recipient_configuration['channels'] ?? [NotificationChannel::InApp->value]),
        );

        foreach ($recipients as $userId) {
            $this->notifications->send(
                organizationId: $schedule->organization_id,
                userId: (string) $userId,
                content: [
                    'title' => "Laporan terjadwal {$schedule->name} siap",
                    'message' => "Report run {$runNumber} sudah selesai dan berkasnya siap diunduh.",
                ],
                channels: $channels,
                priority: NotificationPriority::Normal,
                eventName: 'report_schedule_executed',
                data: ['run_number' => $runNumber, 'export_id' => $exportId],
            );
        }
    }
}
