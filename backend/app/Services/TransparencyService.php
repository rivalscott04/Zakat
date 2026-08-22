<?php

namespace App\Services;

use App\Enums\TransparencyReportStatus;
use App\Enums\TransparencySnapshotStatus;
use App\Enums\TransparencySnapshotType;
use App\Enums\TransparencyVerificationStatus;
use App\Exceptions\ZakatException;
use App\Models\TransparencyReport;
use App\Models\TransparencySnapshot;
use App\Support\OrganizationContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/** PRD 18D sampai 18U — snapshot, verifikasi, publikasi, dan laporan transparansi. */
class TransparencyService
{
    public function __construct(
        private readonly AuditService $audits,
        private readonly TransparencyAggregator $aggregator,
        private readonly PublicTransparencyService $public,
    ) {}

    // -------------------------------------------------------------- snapshot

    public function list(array $filters): LengthAwarePaginator
    {
        return TransparencySnapshot::query()
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['snapshot_type'] ?? null, fn ($query, $type) => $query->where('snapshot_type', $type))
            ->latest('period_start')
            ->paginate(min((int) ($filters['per_page'] ?? config('zakat.pagination.per_page')), config('zakat.pagination.max_per_page')));
    }

    public function find(string $id): TransparencySnapshot
    {
        return TransparencySnapshot::query()->findOrFail($id);
    }

    public function create(array $data): TransparencySnapshot
    {
        [$start, $end] = $this->period($data);

        $snapshot = new TransparencySnapshot;
        $snapshot->fill($data);
        $snapshot->organization_id = OrganizationContext::requireId();
        $snapshot->period_start = $start;
        $snapshot->period_end = $end;
        $snapshot->status = TransparencySnapshotStatus::Draft;
        $snapshot->save();

        $this->audits->record('transparency_snapshot_created', $snapshot, after: $snapshot->only(['snapshot_number', 'period_start', 'period_end', 'snapshot_type']));

        return $snapshot;
    }

    /** PRD 18G §10 — agregasi dijalankan ulang selama snapshot belum diajukan. */
    public function generate(TransparencySnapshot $snapshot): TransparencySnapshot
    {
        $this->assertTransition($snapshot, TransparencySnapshotStatus::Generated);

        $snapshot->data = $this->aggregator->build(
            $snapshot->organization_id,
            $snapshot->period_start->toDateString(),
            $snapshot->period_end->toDateString(),
        );
        $snapshot->status = TransparencySnapshotStatus::Generated;
        $snapshot->generated_at = now();
        $snapshot->generated_by = Auth::id();
        $snapshot->save();

        $this->audits->record('transparency_snapshot_generated', $snapshot);

        return $this->validateData($snapshot);
    }

    /**
     * PRD 18R §28 — pemeriksaan sebelum data boleh naik ke tahap persetujuan.
     */
    public function validateData(TransparencySnapshot $snapshot): TransparencySnapshot
    {
        $data = $snapshot->data ?? [];
        $problems = [];
        $warnings = [];

        if ($data === []) {
            $problems[] = 'Snapshot belum memiliki data. Jalankan generate lebih dulu.';
        }

        if ($snapshot->period_start->gt($snapshot->period_end)) {
            $problems[] = 'Periode tidak valid: tanggal mulai melewati tanggal akhir.';
        }

        $collection = $data['collection']['total_collection'] ?? '0';
        $distributed = $data['distribution']['total_distributed'] ?? '0';
        $fund = $data['fund'] ?? [];

        if (bccomp((string) $collection, '0', 2) === -1 || bccomp((string) $distributed, '0', 2) === -1) {
            $problems[] = 'Nilai penerimaan atau penyaluran negatif.';
        }

        if ($fund !== []) {
            $expected = bcsub(
                bcadd((string) ($fund['opening_balance'] ?? '0'), (string) ($fund['total_inflow'] ?? '0'), 2),
                (string) ($fund['total_outflow'] ?? '0'),
                2,
            );

            // PRD 18I §14 — saldo tersedia wajib sama dengan rumusnya.
            if (bccomp($expected, (string) ($fund['available_balance'] ?? '0'), 2) !== 0) {
                $problems[] = 'Saldo tersedia tidak konsisten dengan saldo awal, pemasukan, dan pengeluaran.';
            }
        }

        if (bccomp((string) $distributed, (string) $collection, 2) === 1) {
            $warnings[] = 'Penyaluran melebihi penerimaan pada periode ini. Pastikan berasal dari saldo periode sebelumnya.';
        }

        if (($data['collection']['transaction_count'] ?? 0) === 0) {
            $warnings[] = 'Tidak ada transaksi penerimaan pada periode ini.';
        }

        $snapshot->verification_status = match (true) {
            $problems !== [] => TransparencyVerificationStatus::Invalid,
            $warnings !== [] => TransparencyVerificationStatus::Warning,
            default => TransparencyVerificationStatus::Valid,
        };
        $snapshot->verification_notes = ['problems' => $problems, 'warnings' => $warnings];
        $snapshot->save();

        $this->audits->record('transparency_snapshot_validated', $snapshot, context: [
            'verification_status' => $snapshot->verification_status->value,
        ]);

        return $snapshot;
    }

    public function submit(TransparencySnapshot $snapshot): TransparencySnapshot
    {
        $this->assertTransition($snapshot, TransparencySnapshotStatus::PendingApproval);

        // PRD 18Z §11 — hanya snapshot yang lolos verifikasi boleh diajukan.
        if ($snapshot->verification_status?->allowsPublication() !== true) {
            throw ZakatException::conflict('Snapshot dengan verifikasi INVALID tidak dapat diajukan.');
        }

        $snapshot->status = TransparencySnapshotStatus::PendingApproval;
        $snapshot->save();

        $this->audits->record('transparency_snapshot_submitted', $snapshot);

        return $snapshot;
    }

    public function approve(TransparencySnapshot $snapshot): TransparencySnapshot
    {
        $this->assertTransition($snapshot, TransparencySnapshotStatus::Approved);

        $snapshot->status = TransparencySnapshotStatus::Approved;
        $snapshot->approved_by = Auth::id();
        $snapshot->approved_at = now();
        $snapshot->save();

        $this->audits->record('transparency_snapshot_approved', $snapshot);

        return $snapshot;
    }

    /** PRD 18Z §12 — hanya snapshot APPROVED yang dapat dipublikasikan. */
    public function publish(TransparencySnapshot $snapshot): TransparencySnapshot
    {
        $this->assertTransition($snapshot, TransparencySnapshotStatus::Published);

        if ($snapshot->verification_status === TransparencyVerificationStatus::Invalid) {
            throw ZakatException::conflict('Snapshot dengan verifikasi INVALID tidak dapat dipublikasikan.');
        }

        $snapshot->status = TransparencySnapshotStatus::Published;
        $snapshot->published_at = now();
        $snapshot->published_by = Auth::id();
        $snapshot->save();

        // Dashboard publik memakai cache, jadi harus dibuang begitu isinya berubah.
        $this->public->forgetCache($snapshot->organization_id);
        $this->audits->record('transparency_snapshot_published', $snapshot, description: "Snapshot {$snapshot->snapshot_number} dipublikasikan.");

        return $snapshot;
    }

    /** PRD 18Z §16 — pencabutan wajib disertai alasan. */
    public function revoke(TransparencySnapshot $snapshot, string $reason): TransparencySnapshot
    {
        $this->assertTransition($snapshot, TransparencySnapshotStatus::Revoked);

        $snapshot->status = TransparencySnapshotStatus::Revoked;
        $snapshot->revoked_at = now();
        $snapshot->revocation_reason = $reason;
        $snapshot->save();

        $this->public->forgetCache($snapshot->organization_id);
        $this->audits->record('transparency_snapshot_revoked', $snapshot, context: ['reason' => $reason], description: $reason);

        return $snapshot;
    }

    // --------------------------------------------------------------- laporan

    public function reports(array $filters): LengthAwarePaginator
    {
        return TransparencyReport::query()
            ->with('snapshot')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('period_start')
            ->paginate(min((int) ($filters['per_page'] ?? config('zakat.pagination.per_page')), config('zakat.pagination.max_per_page')));
    }

    public function findReport(string $id): TransparencyReport
    {
        return TransparencyReport::query()->with('snapshot')->findOrFail($id);
    }

    public function createReport(array $data): TransparencyReport
    {
        $report = new TransparencyReport;
        $report->fill($data);
        $report->organization_id = OrganizationContext::requireId();
        $report->status = TransparencyReportStatus::Draft;
        $report->save();

        $this->audits->record('transparency_report_created', $report, after: $report->only(['report_number', 'title', 'period_start', 'period_end']));

        return $report->load('snapshot');
    }

    public function publishReport(TransparencyReport $report): TransparencyReport
    {
        if ($report->status !== TransparencyReportStatus::Draft) {
            throw ZakatException::invalidTransition('Hanya laporan draft yang dapat dipublikasikan.');
        }

        // PRD 18Z §13 — laporan publik harus bersandar pada snapshot terbit.
        $snapshot = $report->snapshot;

        if ($snapshot === null || $snapshot->status !== TransparencySnapshotStatus::Published) {
            throw ZakatException::conflict('Laporan hanya dapat dipublikasikan bila snapshot rujukannya sudah terbit.');
        }

        $report->status = TransparencyReportStatus::Published;
        $report->published_at = now();
        $report->published_by = Auth::id();
        $report->save();

        $this->public->forgetCache($report->organization_id);
        $this->audits->record('transparency_report_published', $report);

        return $report->load('snapshot');
    }

    public function archiveReport(TransparencyReport $report): TransparencyReport
    {
        $report->status = TransparencyReportStatus::Archived;
        $report->save();

        $this->audits->record('transparency_report_archived', $report);

        return $report->load('snapshot');
    }

    // ----------------------------------------------------------------- bantu

    /** @return array{0: string, 1: string} */
    private function period(array $data): array
    {
        $start = Carbon::parse($data['period_start']);
        $end = Carbon::parse($data['period_end']);

        if ($start->gt($end)) {
            throw ZakatException::conflict('Tanggal mulai tidak boleh melewati tanggal akhir.');
        }

        // Tipe selain CUSTOM merapikan sendiri batas periodenya.
        return match (TransparencySnapshotType::from($data['snapshot_type'])) {
            TransparencySnapshotType::Daily => [$start->toDateString(), $start->toDateString()],
            TransparencySnapshotType::Monthly => [$start->copy()->startOfMonth()->toDateString(), $start->copy()->endOfMonth()->toDateString()],
            TransparencySnapshotType::Quarterly => [$start->copy()->firstOfQuarter()->toDateString(), $start->copy()->lastOfQuarter()->toDateString()],
            TransparencySnapshotType::Yearly => [$start->copy()->startOfYear()->toDateString(), $start->copy()->endOfYear()->toDateString()],
            TransparencySnapshotType::Custom => [$start->toDateString(), $end->toDateString()],
        };
    }

    private function assertTransition(TransparencySnapshot $snapshot, TransparencySnapshotStatus $next): void
    {
        if (! $snapshot->status->canTransitionTo($next)) {
            throw ZakatException::invalidTransition(
                "Snapshot berstatus {$snapshot->status->value} tidak dapat berpindah ke {$next->value}."
            );
        }
    }
}
