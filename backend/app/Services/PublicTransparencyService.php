<?php

namespace App\Services;

use App\Enums\OrganizationStatus;
use App\Enums\TransparencyReportStatus;
use App\Enums\TransparencySnapshotStatus;
use App\Exceptions\ZakatException;
use App\Models\Organization;
use App\Models\TransparencyReport;
use App\Models\TransparencySnapshot;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * PRD 18P — akses publik tanpa login.
 *
 * PRD 18Z §13 membatasinya pada data berstatus PUBLISHED, dan PRD 18Z §19
 * meminta caching. Cache dikunci per organisasi supaya tidak ada kebocoran
 * lintas organisasi.
 */
class PublicTransparencyService
{
    private const CACHE_MINUTES = 10;

    public function __construct(private readonly SettingService $settings) {}

    /** Organisasi yang memang membuka transparansi publik. */
    public function organization(string $code): Organization
    {
        $organization = Organization::query()
            ->withoutGlobalScopes()
            ->where('code', strtoupper($code))
            ->where('status', '!=', OrganizationStatus::Archived)
            ->first();

        if ($organization === null) {
            throw ZakatException::notFound('Organisasi tidak ditemukan.');
        }

        $enabled = $this->settings->effective($organization->getKey())['transparency.public_enabled'] ?? false;

        // PRD 18P §25 — dashboard publik hanya terbuka bila diizinkan organisasi.
        if (! $enabled) {
            throw ZakatException::notFound('Organisasi tidak membuka dashboard transparansi publik.');
        }

        return $organization;
    }

    /** @return array<string, mixed> */
    public function dashboard(string $code): array
    {
        $organization = $this->organization($code);

        return Cache::remember(
            "transparency:public:{$organization->getKey()}",
            now()->addMinutes(self::CACHE_MINUTES),
            function () use ($organization) {
                $snapshot = $this->latestSnapshot($organization->getKey());

                return [
                    'organization' => [
                        'code' => $organization->code,
                        'name' => $organization->name,
                        'type' => $organization->organization_type,
                        'website' => $organization->website,
                    ],
                    'snapshot_number' => $snapshot?->snapshot_number,
                    'period' => $snapshot === null ? null : [
                        'start' => $snapshot->period_start->toDateString(),
                        'end' => $snapshot->period_end->toDateString(),
                    ],
                    'last_updated' => $snapshot?->published_at?->toIso8601String(),
                    'data' => $snapshot?->data,
                    'reports' => $this->reports($organization->getKey()),
                ];
            },
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function reports(string $organizationId): array
    {
        return TransparencyReport::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('status', TransparencyReportStatus::Published)
            ->latest('period_start')
            ->limit(20)
            ->get()
            ->map(fn (TransparencyReport $report) => [
                'report_number' => $report->report_number,
                'title' => $report->title,
                'period_start' => $report->period_start->toDateString(),
                'period_end' => $report->period_end->toDateString(),
                'published_at' => $report->published_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * PRD 18T §34 — pembuktian bahwa satu transaksi tercatat, tanpa membuka
     * identitas pemberi maupun penerima.
     *
     * @return array<string, mixed>
     */
    public function verify(string $reference): array
    {
        $reference = strtoupper(trim($reference));

        $sources = [
            'collections' => ['number' => 'collection_number', 'type' => 'Penerimaan', 'amount' => 'paid_amount', 'date' => 'collection_date'],
            'payments' => ['number' => 'payment_number', 'type' => 'Pembayaran', 'amount' => 'amount', 'date' => 'created_at'],
            'distributions' => ['number' => 'distribution_number', 'type' => 'Penyaluran', 'amount' => 'distributed_amount', 'date' => 'distribution_date'],
        ];

        foreach ($sources as $table => $columns) {
            $row = DB::table($table)->where($columns['number'], $reference)->first();

            if ($row === null) {
                continue;
            }

            $organization = Organization::query()->withoutGlobalScopes()->find($row->organization_id);

            // Verifikasi publik hanya melayani organisasi yang membuka transparansi.
            if (($this->settings->effective($row->organization_id)['transparency.public_enabled'] ?? false) !== true) {
                break;
            }

            return [
                'reference' => $reference,
                'transaction_type' => $columns['type'],
                'amount' => (string) $row->{$columns['amount']},
                'currency' => $row->currency ?? 'IDR',
                'date' => $row->{$columns['date']},
                'status' => $row->status,
                'organization' => ['code' => $organization?->code, 'name' => $organization?->name],
            ];
        }

        throw ZakatException::notFound('Referensi transaksi tidak ditemukan.');
    }

    public function forgetCache(string $organizationId): void
    {
        Cache::forget("transparency:public:{$organizationId}");
    }

    private function latestSnapshot(string $organizationId): ?TransparencySnapshot
    {
        return TransparencySnapshot::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('status', TransparencySnapshotStatus::Published)
            ->latest('period_end')
            ->first();
    }
}
