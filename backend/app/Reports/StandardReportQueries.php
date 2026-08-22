<?php

namespace App\Reports;

use App\Exceptions\ZakatException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * PRD 19B §3 — modul reporting hanya membaca, tidak memiliki data.
 *
 * Seluruh query menyaring organization_id secara eksplisit (PRD 19W §5 dan §6),
 * dan nilai uang dikembalikan sebagai string agar tidak melewati float
 * (Core PRD §12).
 */
class StandardReportQueries
{
    /**
     * @param  array<string, mixed>  $params
     * @return array{columns: array<int, array{key: string, label: string, type: string}>, rows: array<int, array<string, mixed>>, summary: array<string, mixed>}
     */
    public function run(string $code, string $organizationId, array $params): array
    {
        return match ($code) {
            'ZAKATCOLLECTION' => $this->zakatCollection($organizationId, $params),
            'FUNDPOSITION' => $this->fundPosition($organizationId),
            'DISTRIBUTIONSUMMARY' => $this->distributionSummary($organizationId, $params),
            'ASNAFDISTRIBUTION' => $this->asnafDistribution($organizationId, $params),
            'PROGRAMPERFORMANCE' => $this->programPerformance($organizationId),
            'MUZAKKISUMMARY' => $this->muzakkiSummary($organizationId),
            'MUSTAHIKSUMMARY' => $this->mustahikSummary($organizationId),
            'BANKRECONCILIATION' => $this->bankReconciliation($organizationId, $params),
            'FINANCIALPOSITION' => $this->financialPosition($organizationId, $params),
            'ACTIVITYSUMMARY' => $this->activitySummary($organizationId, $params),
            'AUDITSUMMARY' => $this->auditSummary($organizationId, $params),
            default => throw ZakatException::notFound("Laporan bawaan [{$code}] tidak dikenal."),
        };
    }

    // ------------------------------------------------------------ collection

    private function zakatCollection(string $organizationId, array $params): array
    {
        [$from, $to] = $this->period($params);

        $rows = DB::table('collections as c')
            ->leftJoin('zakat_types as z', 'z.id', '=', 'c.zakat_type_id')
            ->where('c.organization_id', $organizationId)
            ->whereNull('c.deleted_at')
            ->whereBetween('c.collection_date', [$from, $to])
            ->groupBy('z.code', 'z.name', 'c.status')
            ->orderBy('z.code')
            ->selectRaw("coalesce(z.code, 'TANPA_JENIS') as zakat_code, coalesce(z.name, 'Tanpa jenis') as zakat_name, c.status,
                count(*) as total_transaksi,
                sum(c.expected_amount)::text as expected_amount,
                sum(c.paid_amount)::text as paid_amount,
                sum(c.remaining_amount)::text as remaining_amount")
            ->get();

        return [
            'columns' => $this->columns([
                ['zakat_code', 'Kode zakat'],
                ['zakat_name', 'Jenis zakat'],
                ['status', 'Status'],
                ['total_transaksi', 'Transaksi', 'number'],
                ['expected_amount', 'Tagihan', 'money'],
                ['paid_amount', 'Terbayar', 'money'],
                ['remaining_amount', 'Sisa', 'money'],
            ]),
            'rows' => $this->rows($rows),
            'summary' => [
                'periode' => "{$from} sampai {$to}",
                'total_terbayar' => $this->sum($rows, 'paid_amount'),
                'total_sisa' => $this->sum($rows, 'remaining_amount'),
            ],
        ];
    }

    // ------------------------------------------------------------------ fund

    private function fundPosition(string $organizationId): array
    {
        $rows = DB::table('funds')
            ->where('organization_id', $organizationId)
            ->whereNull('deleted_at')
            ->orderBy('fund_code')
            ->selectRaw('fund_code, name, fund_type, status,
                opening_balance::text as opening_balance,
                current_balance::text as current_balance,
                available_balance::text as available_balance,
                reserved_balance::text as reserved_balance,
                allocated_balance::text as allocated_balance,
                distributed_balance::text as distributed_balance')
            ->get();

        return [
            'columns' => $this->columns([
                ['fund_code', 'Kode dana'],
                ['name', 'Nama dana'],
                ['fund_type', 'Jenis'],
                ['status', 'Status'],
                ['opening_balance', 'Saldo awal', 'money'],
                ['current_balance', 'Saldo kini', 'money'],
                ['available_balance', 'Tersedia', 'money'],
                ['reserved_balance', 'Dipesan', 'money'],
                ['allocated_balance', 'Dialokasikan', 'money'],
                ['distributed_balance', 'Tersalurkan', 'money'],
            ]),
            'rows' => $this->rows($rows),
            'summary' => [
                'total_saldo' => $this->sum($rows, 'current_balance'),
                'total_tersedia' => $this->sum($rows, 'available_balance'),
            ],
        ];
    }

    // ---------------------------------------------------------- distribution

    private function distributionSummary(string $organizationId, array $params): array
    {
        [$from, $to] = $this->period($params);

        $rows = DB::table('distributions')
            ->where('organization_id', $organizationId)
            ->whereBetween(DB::raw('coalesce(distribution_date, created_at::date)'), [$from, $to])
            ->groupBy('distribution_type', 'status')
            ->orderBy('distribution_type')
            ->selectRaw('distribution_type, status,
                count(*) as total_penyaluran,
                sum(requested_amount)::text as requested_amount,
                sum(approved_amount)::text as approved_amount,
                sum(distributed_amount)::text as distributed_amount')
            ->get();

        return [
            'columns' => $this->columns([
                ['distribution_type', 'Jenis penyaluran'],
                ['status', 'Status'],
                ['total_penyaluran', 'Jumlah', 'number'],
                ['requested_amount', 'Diajukan', 'money'],
                ['approved_amount', 'Disetujui', 'money'],
                ['distributed_amount', 'Tersalurkan', 'money'],
            ]),
            'rows' => $this->rows($rows),
            'summary' => [
                'periode' => "{$from} sampai {$to}",
                'total_tersalurkan' => $this->sum($rows, 'distributed_amount'),
            ],
        ];
    }

    private function asnafDistribution(string $organizationId, array $params): array
    {
        [$from, $to] = $this->period($params);

        $rows = DB::table('distributions as d')
            ->join('mustahiks as m', 'm.id', '=', 'd.mustahik_id')
            ->leftJoin('mustahik_asnaf as a', function ($join) {
                $join->on('a.mustahik_id', '=', 'm.id')->where('a.primary_asnaf', true);
            })
            ->where('d.organization_id', $organizationId)
            ->whereBetween(DB::raw('coalesce(d.distribution_date, d.created_at::date)'), [$from, $to])
            ->groupBy('a.asnaf_code')
            ->orderBy('a.asnaf_code')
            ->selectRaw("coalesce(a.asnaf_code, 'TANPA_ASNAF') as asnaf_code,
                count(distinct m.id) as total_mustahik,
                count(*) as total_penyaluran,
                sum(d.distributed_amount)::text as distributed_amount")
            ->get();

        return [
            'columns' => $this->columns([
                ['asnaf_code', 'Asnaf'],
                ['total_mustahik', 'Mustahik', 'number'],
                ['total_penyaluran', 'Penyaluran', 'number'],
                ['distributed_amount', 'Nilai tersalurkan', 'money'],
            ]),
            'rows' => $this->rows($rows),
            'summary' => [
                'periode' => "{$from} sampai {$to}",
                'total_tersalurkan' => $this->sum($rows, 'distributed_amount'),
            ],
        ];
    }

    // --------------------------------------------------------------- program

    private function programPerformance(string $organizationId): array
    {
        // Subquery, bukan join berantai: menggabung tiga tabel anak sekaligus
        // akan menggandakan baris dan membuat penjumlahannya salah.
        $rows = DB::table('programs as p')
            ->where('p.organization_id', $organizationId)
            ->orderBy('p.program_code')
            ->selectRaw('p.program_code, p.name, p.status,
                coalesce((select sum(b.budget_amount) from program_budgets b where b.program_id = p.id), 0)::text as budget_amount,
                (select count(*) from program_enrollments e where e.program_id = p.id) as total_penerima,
                (select count(*) from distributions d where d.program_id = p.id) as total_penyaluran,
                coalesce((select sum(d.distributed_amount) from distributions d where d.program_id = p.id), 0)::text as distributed_amount')
            ->get();

        return [
            'columns' => $this->columns([
                ['program_code', 'Kode program'],
                ['name', 'Program'],
                ['status', 'Status'],
                ['budget_amount', 'Anggaran', 'money'],
                ['total_penerima', 'Penerima manfaat', 'number'],
                ['total_penyaluran', 'Penyaluran', 'number'],
                ['distributed_amount', 'Realisasi', 'money'],
            ]),
            'rows' => $this->rows($rows),
            'summary' => [
                'total_anggaran' => $this->sum($rows, 'budget_amount'),
                'total_realisasi' => $this->sum($rows, 'distributed_amount'),
            ],
        ];
    }

    // ------------------------------------------------------- muzaki mustahik

    private function muzakkiSummary(string $organizationId): array
    {
        $rows = DB::table('muzakis as m')
            ->leftJoin('collections as c', function ($join) {
                $join->on('c.muzaki_id', '=', 'm.id')->whereNull('c.deleted_at');
            })
            ->where('m.organization_id', $organizationId)
            ->whereNull('m.deleted_at')
            ->groupBy('m.muzaki_type', 'm.status')
            ->orderBy('m.muzaki_type')
            ->selectRaw('m.muzaki_type, m.status,
                count(distinct m.id) as total_muzaki,
                count(c.id) as total_transaksi,
                coalesce(sum(c.paid_amount), 0)::text as paid_amount')
            ->get();

        return [
            'columns' => $this->columns([
                ['muzaki_type', 'Jenis muzaki'],
                ['status', 'Status'],
                ['total_muzaki', 'Jumlah muzaki', 'number'],
                ['total_transaksi', 'Transaksi', 'number'],
                ['paid_amount', 'Kontribusi', 'money'],
            ]),
            'rows' => $this->rows($rows),
            'summary' => ['total_kontribusi' => $this->sum($rows, 'paid_amount')],
        ];
    }

    private function mustahikSummary(string $organizationId): array
    {
        $rows = DB::table('mustahiks')
            ->where('organization_id', $organizationId)
            ->whereNull('deleted_at')
            ->groupBy('mustahik_type', 'status', 'verification_status', 'eligibility_status')
            ->orderBy('mustahik_type')
            ->selectRaw('mustahik_type, status, verification_status, eligibility_status, count(*) as total_mustahik')
            ->get();

        return [
            'columns' => $this->columns([
                ['mustahik_type', 'Jenis mustahik'],
                ['status', 'Status'],
                ['verification_status', 'Verifikasi'],
                ['eligibility_status', 'Kelayakan'],
                ['total_mustahik', 'Jumlah', 'number'],
            ]),
            'rows' => $this->rows($rows),
            'summary' => ['total_mustahik' => array_sum(array_column($this->rows($rows), 'total_mustahik'))],
        ];
    }

    // --------------------------------------------------------------- banking

    private function bankReconciliation(string $organizationId, array $params): array
    {
        [$from, $to] = $this->period($params);

        $rows = DB::table('reconciliation_sessions as s')
            ->leftJoin('bank_accounts as b', 'b.id', '=', 's.bank_account_id')
            ->where('s.organization_id', $organizationId)
            ->whereBetween('s.period_start', [$from, $to])
            ->orderBy('s.period_start')
            ->selectRaw('s.session_number, b.account_name, s.period_start, s.period_end, s.status,
                s.matched_amount::text as matched_amount,
                s.unmatched_amount::text as unmatched_amount,
                s.difference_amount::text as difference_amount')
            ->get();

        return [
            'columns' => $this->columns([
                ['session_number', 'Nomor sesi'],
                ['account_name', 'Rekening'],
                ['period_start', 'Mulai', 'date'],
                ['period_end', 'Akhir', 'date'],
                ['status', 'Status'],
                ['matched_amount', 'Cocok', 'money'],
                ['unmatched_amount', 'Belum cocok', 'money'],
                ['difference_amount', 'Selisih', 'money'],
            ]),
            'rows' => $this->rows($rows),
            'summary' => [
                'periode' => "{$from} sampai {$to}",
                'total_selisih' => $this->sum($rows, 'difference_amount'),
            ],
        ];
    }

    // ------------------------------------------------------------- financial

    private function financialPosition(string $organizationId, array $params): array
    {
        [$from, $to] = $this->period($params);

        $rows = DB::table('journal_lines as l')
            ->join('journal_entries as j', 'j.id', '=', 'l.journal_entry_id')
            ->join('chart_of_accounts as a', 'a.id', '=', 'l.account_id')
            ->where('j.organization_id', $organizationId)
            // Hanya jurnal yang sudah diposting yang membentuk posisi keuangan.
            ->where('j.status', 'posted')
            ->whereBetween('j.journal_date', [$from, $to])
            ->groupBy('a.account_code', 'a.account_name', 'a.account_type', 'a.normal_balance')
            ->orderBy('a.account_code')
            ->selectRaw("a.account_code, a.account_name, a.account_type, a.normal_balance,
                sum(l.debit_amount)::text as debit_amount,
                sum(l.credit_amount)::text as credit_amount,
                (case when a.normal_balance = 'debit'
                      then sum(l.debit_amount) - sum(l.credit_amount)
                      else sum(l.credit_amount) - sum(l.debit_amount) end)::text as balance")
            ->get();

        return [
            'columns' => $this->columns([
                ['account_code', 'Kode akun'],
                ['account_name', 'Nama akun'],
                ['account_type', 'Tipe'],
                ['debit_amount', 'Debit', 'money'],
                ['credit_amount', 'Kredit', 'money'],
                ['balance', 'Saldo', 'money'],
            ]),
            'rows' => $this->rows($rows),
            'summary' => [
                'periode' => "{$from} sampai {$to}",
                'total_debit' => $this->sum($rows, 'debit_amount'),
                'total_kredit' => $this->sum($rows, 'credit_amount'),
            ],
        ];
    }

    // ----------------------------------------------------------- operational

    private function activitySummary(string $organizationId, array $params): array
    {
        [$from, $to] = $this->period($params);

        $counts = [
            'Penerimaan dibuat' => DB::table('collections')->whereNull('deleted_at')->whereBetween('collection_date', [$from, $to]),
            'Pembayaran diterima' => DB::table('payments')->where('status', 'paid')->whereBetween(DB::raw('paid_at::date'), [$from, $to]),
            'Penyaluran dibuat' => DB::table('distributions')->whereBetween(DB::raw('coalesce(distribution_date, created_at::date)'), [$from, $to]),
            'Asesmen diselesaikan' => DB::table('assessments')->whereNotNull('approved_at')->whereBetween(DB::raw('approved_at::date'), [$from, $to]),
            'Jurnal diposting' => DB::table('journal_entries')->where('status', 'posted')->whereBetween('journal_date', [$from, $to]),
        ];

        $rows = [];

        foreach ($counts as $label => $query) {
            $rows[] = ['aktivitas' => $label, 'jumlah' => $query->where('organization_id', $organizationId)->count()];
        }

        return [
            'columns' => $this->columns([['aktivitas', 'Aktivitas'], ['jumlah', 'Jumlah', 'number']]),
            'rows' => $rows,
            'summary' => ['periode' => "{$from} sampai {$to}"],
        ];
    }

    // ----------------------------------------------------------------- audit

    private function auditSummary(string $organizationId, array $params): array
    {
        [$from, $to] = $this->period($params);

        $rows = DB::table('audit_logs')
            ->where('organization_id', $organizationId)
            ->whereBetween(DB::raw('occurred_at::date'), [$from, $to])
            ->groupBy('module_code', 'event_category', 'severity')
            ->orderBy('module_code')
            ->selectRaw('module_code, event_category, severity, count(*) as total_peristiwa')
            ->get();

        return [
            'columns' => $this->columns([
                ['module_code', 'Modul'],
                ['event_category', 'Kategori'],
                ['severity', 'Tingkat'],
                ['total_peristiwa', 'Peristiwa', 'number'],
            ]),
            'rows' => $this->rows($rows),
            'summary' => [
                'periode' => "{$from} sampai {$to}",
                'total_peristiwa' => array_sum(array_column($this->rows($rows), 'total_peristiwa')),
            ],
        ];
    }

    // ---------------------------------------------------------------- bantu

    /** @return array{0: string, 1: string} */
    private function period(array $params): array
    {
        $from = $params['date_from'] ?? now()->startOfMonth()->toDateString();
        $to = $params['date_to'] ?? now()->toDateString();

        // PRD 19W §11 — rentang tanggal terbalik tidak boleh diproses.
        if (Carbon::parse($from)->gt(Carbon::parse($to))) {
            throw ZakatException::conflict('Tanggal mulai tidak boleh melewati tanggal akhir.');
        }

        return [Carbon::parse($from)->toDateString(), Carbon::parse($to)->toDateString()];
    }

    /** @param array<int, array<int, string>> $definitions */
    private function columns(array $definitions): array
    {
        return array_map(fn (array $column) => [
            'key' => $column[0],
            'label' => $column[1],
            'type' => $column[2] ?? 'text',
        ], $definitions);
    }

    private function rows(iterable $rows): array
    {
        return array_map(fn ($row) => (array) $row, is_array($rows) ? $rows : iterator_to_array($rows));
    }

    /** Penjumlahan uang memakai bcmath, bukan float (Core PRD §12). */
    private function sum(iterable $rows, string $column): string
    {
        $total = '0';

        foreach ($rows as $row) {
            $total = bcadd($total, (string) (((array) $row)[$column] ?? '0'), 2);
        }

        return $total;
    }
}
