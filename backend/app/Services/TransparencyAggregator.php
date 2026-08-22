<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * PRD 18B §4 — modul transparansi hanya membaca dan mengagregasi.
 *
 * Seluruh keluaran berupa angka agregat. Tidak ada satu pun kolom identitas
 * yang ikut, sesuai larangan PRD 18B §3 dan PRD 18Z §4 sampai §10.
 */
class TransparencyAggregator
{
    /** @return array<string, mixed> */
    public function build(string $organizationId, string $periodStart, string $periodEnd): array
    {
        $collection = $this->collection($organizationId, $periodStart, $periodEnd);
        $distribution = $this->distribution($organizationId, $periodStart, $periodEnd);
        $fund = $this->fund($organizationId, $periodStart, $periodEnd);

        return [
            'organization' => $this->organization($organizationId),
            'period' => ['start' => $periodStart, 'end' => $periodEnd],
            'collection' => $collection,
            'fund' => $fund,
            'distribution' => $distribution,
            'asnaf' => $this->asnaf($organizationId, $periodStart, $periodEnd, $distribution['total_distributed']),
            'regions' => $this->regions($organizationId, $periodStart, $periodEnd),
            'programs' => $this->programs($organizationId),
            'metrics' => $this->metrics($collection['total_collection'], $distribution['total_distributed'], $fund['available_balance']),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function organization(string $organizationId): array
    {
        $organization = DB::table('organizations')
            ->where('id', $organizationId)
            ->select('code', 'name', 'organization_type', 'website', 'currency')
            ->first();

        return (array) ($organization ?? []);
    }

    /** PRD 18H §12. */
    private function collection(string $organizationId, string $from, string $to): array
    {
        $rows = DB::table('collections as c')
            ->leftJoin('zakat_types as t', 't.id', '=', 'c.zakat_type_id')
            ->leftJoin('zakat_categories as g', 'g.id', '=', 't.zakat_category_id')
            ->where('c.organization_id', $organizationId)
            ->whereNull('c.deleted_at')
            ->whereBetween('c.collection_date', [$from, $to])
            ->groupBy('g.code')
            ->selectRaw("coalesce(g.code, 'LAINNYA') as category_code,
                count(*) as transaction_count,
                sum(c.paid_amount)::text as paid_amount")
            ->get();

        return [
            'total_collection' => $this->sum($rows, 'paid_amount'),
            'transaction_count' => (int) array_sum(array_column($this->rows($rows), 'transaction_count')),
            'breakdown' => $this->rows($rows),
        ];
    }

    /** PRD 18I §14 — saldo tersedia = saldo awal + masuk - keluar. */
    private function fund(string $organizationId, string $from, string $to): array
    {
        $opening = DB::table('funds')
            ->where('organization_id', $organizationId)
            ->whereNull('deleted_at')
            ->selectRaw('coalesce(sum(opening_balance), 0)::text as total')
            ->value('total');

        $flow = DB::table('fund_movements')
            ->where('organization_id', $organizationId)
            ->where('status', 'posted')
            ->whereBetween(DB::raw('effective_at::date'), [$from, $to])
            ->selectRaw("coalesce(sum(case when direction = 'in' then amount else 0 end), 0)::text as inflow,
                coalesce(sum(case when direction = 'out' then amount else 0 end), 0)::text as outflow")
            ->first();

        $inflow = $flow->inflow ?? '0';
        $outflow = $flow->outflow ?? '0';

        return [
            'opening_balance' => bcadd((string) $opening, '0', 2),
            'total_inflow' => bcadd($inflow, '0', 2),
            'total_outflow' => bcadd($outflow, '0', 2),
            'available_balance' => bcsub(bcadd((string) $opening, $inflow, 2), $outflow, 2),
            'by_category' => $this->rows(DB::table('funds')
                ->where('organization_id', $organizationId)
                ->whereNull('deleted_at')
                ->groupBy('fund_type')
                ->selectRaw('fund_type, sum(current_balance)::text as balance')
                ->get()),
        ];
    }

    /** PRD 18J §16. Jumlah penerima hanya berupa cacah, tanpa identitas. */
    private function distribution(string $organizationId, string $from, string $to): array
    {
        $summary = DB::table('distributions')
            ->where('organization_id', $organizationId)
            ->where('status', 'completed')
            ->whereBetween(DB::raw('coalesce(distribution_date, created_at::date)'), [$from, $to])
            ->selectRaw('coalesce(sum(distributed_amount), 0)::text as total,
                count(*) as distribution_count,
                count(distinct mustahik_id) as beneficiary_count,
                count(distinct program_id) as program_count')
            ->first();

        return [
            'total_distributed' => bcadd((string) ($summary->total ?? '0'), '0', 2),
            'distribution_count' => (int) ($summary->distribution_count ?? 0),
            'beneficiary_count' => (int) ($summary->beneficiary_count ?? 0),
            'program_count' => (int) ($summary->program_count ?? 0),
        ];
    }

    /** PRD 18K §18. */
    private function asnaf(string $organizationId, string $from, string $to, string $total): array
    {
        $rows = DB::table('distributions as d')
            ->join('mustahiks as m', 'm.id', '=', 'd.mustahik_id')
            ->leftJoin('mustahik_asnaf as a', function ($join) {
                $join->on('a.mustahik_id', '=', 'm.id')->where('a.primary_asnaf', true);
            })
            ->where('d.organization_id', $organizationId)
            ->where('d.status', 'completed')
            ->whereBetween(DB::raw('coalesce(d.distribution_date, d.created_at::date)'), [$from, $to])
            ->groupBy('a.asnaf_code')
            ->selectRaw("coalesce(a.asnaf_code, 'LAINNYA') as asnaf_code,
                count(distinct m.id) as beneficiary_count,
                sum(d.distributed_amount)::text as amount")
            ->get();

        return array_map(function (array $row) use ($total) {
            $row['percentage'] = bccomp($total, '0', 2) === 1
                ? bcmul(bcdiv((string) $row['amount'], $total, 6), '100', 2)
                : '0.00';

            return $row;
        }, $this->rows($rows));
    }

    /** PRD 18M §21 — hanya kode wilayah, bukan alamat detail. */
    private function regions(string $organizationId, string $from, string $to): array
    {
        return $this->rows(DB::table('distributions as d')
            ->join('mustahiks as m', 'm.id', '=', 'd.mustahik_id')
            ->leftJoin('mustahik_addresses as ad', function ($join) {
                $join->on('ad.mustahik_id', '=', 'm.id')->where('ad.is_primary', true);
            })
            ->where('d.organization_id', $organizationId)
            ->where('d.status', 'completed')
            ->whereBetween(DB::raw('coalesce(d.distribution_date, d.created_at::date)'), [$from, $to])
            ->groupBy('ad.province_code', 'ad.regency_code')
            ->selectRaw("coalesce(ad.province_code, 'TIDAK_DIKETAHUI') as province_code,
                coalesce(ad.regency_code, 'TIDAK_DIKETAHUI') as regency_code,
                count(distinct m.id) as beneficiary_count,
                sum(d.distributed_amount)::text as amount")
            ->get());
    }

    /** PRD 18L §19 — hanya program yang memang terbuka untuk publik. */
    private function programs(string $organizationId): array
    {
        return $this->rows(DB::table('programs as p')
            ->where('p.organization_id', $organizationId)
            ->where('p.visibility', 'public')
            ->whereNull('p.archived_at')
            ->orderBy('p.program_code')
            ->selectRaw("p.program_code, p.name, p.status, p.start_date, p.end_date,
                (select count(*) from program_enrollments e where e.program_id = p.id and e.status = 'approved') as beneficiary_count,
                coalesce((select sum(d.distributed_amount) from distributions d where d.program_id = p.id and d.status = 'completed'), 0)::text as distributed_amount")
            ->get());
    }

    /** PRD 18S §31 dan §32. */
    private function metrics(string $collection, string $distributed, string $available): array
    {
        return [
            'distribution_rate' => $this->ratio($distributed, $collection),
            'fund_utilization' => $this->ratio($distributed, $available),
        ];
    }

    private function ratio(string $numerator, string $denominator): string
    {
        return bccomp($denominator, '0', 2) === 1
            ? bcmul(bcdiv($numerator, $denominator, 6), '100', 2)
            : '0.00';
    }

    private function rows(iterable $rows): array
    {
        return array_map(fn ($row) => (array) $row, is_array($rows) ? $rows : iterator_to_array($rows));
    }

    private function sum(iterable $rows, string $column): string
    {
        $total = '0';

        foreach ($rows as $row) {
            $total = bcadd($total, (string) (((array) $row)[$column] ?? '0'), 2);
        }

        return $total;
    }
}
