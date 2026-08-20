<?php

namespace App\Services;

use App\Exceptions\ZakatException;
use App\Models\AccountingEvent;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    /**
     * F-18 — status jurnal yang nilainya ikut terhitung pada ledger.
     *
     * `reversed` ikut karena jurnal itu benar-benar pernah diposting dan
     * pembatalannya diwakili oleh jurnal reversal tersendiri. Mengeluarkannya
     * akan membuat efek jurnal asal hilang dua kali: sekali karena tidak lagi
     * dihitung, sekali lagi karena jurnal reversal dihitung.
     */
    private const LEDGER_STATUSES = ['posted', 'reversed'];

    public function __construct(private readonly AuditService $audit) {}

    public function accounts(array $filters): LengthAwarePaginator
    {
        return ChartOfAccount::with('parent')->when($filters['account_type'] ?? null, fn ($q, $v) => $q->where('account_type', $v))->where('status', 'active')->orderBy('account_code')->paginate(50);
    }

    public function createAccount(array $data): ChartOfAccount
    {
        if (($data['parent_id'] ?? null) && ! ChartOfAccount::whereKey($data['parent_id'])->where('is_postable', false)->exists()) {
            throw ZakatException::conflict('Parent account harus valid dan non-postable.');
        } $account = ChartOfAccount::create($data);
        $this->audit->record('account_created', $account);

        return $account;
    }

    public function periods(): LengthAwarePaginator
    {
        return AccountingPeriod::latest('start_date')->paginate(24);
    }

    public function createPeriod(array $data): AccountingPeriod
    {
        $period = AccountingPeriod::create($data + ['status' => 'open']);
        $this->audit->record('period_created', $period);

        return $period;
    }

    public function lockPeriod(AccountingPeriod $period): AccountingPeriod
    {
        if ($period->status !== 'open') {
            throw ZakatException::invalidTransition('Hanya period open yang dapat dikunci.');
        } $period->forceFill(['status' => 'locked'])->saveQuietly();
        $this->audit->record('period_locked', $period);

        return $period;
    }

    public function closePeriod(AccountingPeriod $period): AccountingPeriod
    {
        if ($period->status !== 'locked') {
            throw ZakatException::invalidTransition('Period harus locked sebelum ditutup.');
        } $period->forceFill(['status' => 'closed', 'closed_at' => now(), 'closed_by' => auth()->id()])->saveQuietly();
        $this->audit->record('period_closed', $period);

        return $period;
    }

    public function journals(array $filters): LengthAwarePaginator
    {
        return JournalEntry::with(['lines.account', 'period'])->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->latest('journal_date')->paginate(30);
    }

    public function createJournal(array $data): JournalEntry
    {
        return DB::transaction(function () use ($data) {
            $period = AccountingPeriod::find($data['accounting_period_id']) ?? throw ZakatException::notFound('Accounting period tidak ditemukan.');
            // PRD 08 — period locked maupun closed sama-sama tertutup untuk
            // pencatatan baru. Bedanya hanya locked masih bisa dibuka kembali.
            if (in_array($period->status, ['locked', 'closed'], true)) {
                throw ZakatException::invalidTransition("Period berstatus {$period->status} tidak menerima journal baru.");
            }
            $journal = JournalEntry::create(['journal_number' => app(BusinessNumberService::class)->next('JRN'), 'journal_date' => $data['journal_date'], 'accounting_period_id' => $period->id, 'journal_type' => $data['journal_type'] ?? 'manual', 'source_type' => $data['source_type'] ?? null, 'source_id' => $data['source_id'] ?? null, 'reference_number' => $data['reference_number'] ?? null, 'description' => $data['description'], 'status' => 'draft', 'created_by' => auth()->id()]);
            foreach ($data['lines'] as $index => $line) {
                $account = ChartOfAccount::find($line['account_id']) ?? throw ZakatException::notFound('Account journal tidak ditemukan.');
                if (! $account->is_postable) {
                    throw ZakatException::conflict('Parent account tidak dapat menerima posting.');
                } if (bccomp((string) ($line['debit_amount'] ?? '0'), '0', 2) > 0 && bccomp((string) ($line['credit_amount'] ?? '0'), '0', 2) > 0) {
                    throw ZakatException::conflict('Journal line tidak boleh debit dan credit sekaligus.');
                } if (bccomp((string) ($line['debit_amount'] ?? '0'), '0', 2) === 0 && bccomp((string) ($line['credit_amount'] ?? '0'), '0', 2) === 0) {
                    throw ZakatException::conflict('Journal line tidak boleh bernilai nol.');
                } JournalLine::create(['journal_entry_id' => $journal->id, 'line_number' => $index + 1, 'account_id' => $account->id, 'description' => $line['description'] ?? null, 'debit_amount' => $line['debit_amount'] ?? '0', 'credit_amount' => $line['credit_amount'] ?? '0', 'currency' => $data['currency'] ?? 'IDR']);
            }
            $this->assertBalanced($journal);
            $this->audit->record('journal_created', $journal);

            return $this->findJournal($journal->id);
        });
    }

    public function findJournal(string $id): JournalEntry
    {
        return JournalEntry::with(['lines.account', 'period'])->find($id) ?? throw ZakatException::notFound('Journal tidak ditemukan.');
    }

    public function submit(JournalEntry $journal): JournalEntry
    {
        $this->assertBalanced($journal);
        if ($journal->status !== 'draft') {
            throw ZakatException::invalidTransition('Hanya draft journal yang dapat disubmit.');
        } $journal->forceFill(['status' => 'pending_approval'])->saveQuietly();
        $this->audit->record('journal_submitted', $journal);

        return $journal;
    }

    public function approve(JournalEntry $journal): JournalEntry
    {
        if ($journal->status !== 'pending_approval') {
            throw ZakatException::invalidTransition('Journal tidak menunggu approval.');
        } if ($journal->created_by && $journal->created_by === auth()->id()) {
            throw ZakatException::forbidden('Maker tidak dapat menyetujui journal sendiri.');
        } $journal->forceFill(['status' => 'approved'])->saveQuietly();
        $this->audit->record('journal_approved', $journal);

        return $journal;
    }

    public function post(JournalEntry $journal): JournalEntry
    {
        $this->assertBalanced($journal);
        // Hanya jurnal yang sudah disetujui yang boleh diposting. Menerima draft
        // di sini membuat submit dan approve, termasuk maker-checker, bisa dilewati.
        if ($journal->status !== 'approved') {
            throw ZakatException::invalidTransition('Journal harus approved sebelum diposting.');
        } if (in_array($journal->period->status, ['locked', 'closed'], true)) {
            throw ZakatException::invalidTransition("Period berstatus {$journal->period->status} tidak dapat diposting.");
        } $journal->forceFill(['status' => 'posted', 'posted_by' => auth()->id(), 'posted_at' => now()])->saveQuietly();
        $this->audit->record('journal_posted', $journal);

        return $journal;
    }

    public function reverse(JournalEntry $journal, array $data): JournalEntry
    {
        if ($journal->status !== 'posted') {
            throw ZakatException::invalidTransition('Hanya posted journal yang dapat direverse.');
        }

        // F-10 — reversal dan penandaan jurnal asal harus satu satuan. Kalau
        // dipisah, kegagalan di tengah menyisakan reversal terposting sementara
        // jurnal asal masih dihitung, sehingga nilainya masuk dua kali.
        return DB::transaction(function () use ($journal, $data) {
            $lines = $journal->lines->map(fn ($line) => ['account_id' => $line->account_id, 'description' => 'Reversal '.$journal->journal_number, 'debit_amount' => $line->credit_amount, 'credit_amount' => $line->debit_amount])->all();
            $reversal = $this->createJournal(['accounting_period_id' => $journal->accounting_period_id, 'journal_date' => $data['journal_date'] ?? now()->toDateString(), 'journal_type' => 'reversal', 'reference_number' => $journal->journal_number, 'description' => $data['reason'], 'lines' => $lines]);
            $reversal->forceFill(['status' => 'posted', 'posted_by' => auth()->id(), 'posted_at' => now(), 'reversal_of_id' => $journal->id])->saveQuietly();
            $journal->forceFill(['status' => 'reversed'])->saveQuietly();
            $this->audit->record('journal_reversed', $reversal, context: ['original_journal_id' => $journal->id]);

            return $this->findJournal($reversal->id);
        });
    }

    /**
     * F-11 — accounting_events punya unique (organization_id, event_type,
     * source_type, source_id). Tanpa pengecekan ini, duplikat memunculkan
     * pelanggaran constraint mentah yang berakhir jadi 500.
     */
    public function event(array $data): AccountingEvent
    {
        $existing = AccountingEvent::where('event_type', $data['event_type'])
            ->where('source_type', $data['source_type'])
            ->where('source_id', $data['source_id'])
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return AccountingEvent::create($data + ['status' => 'pending']);
    }

    /**
     * F-12 — dulu menarik seluruh journal line ke memori. Sekarang berpaginasi
     * sesuai Core PRD §23 dan CLAUDE.md §23.
     */
    public function ledger(array $filters): LengthAwarePaginator
    {
        return JournalLine::query()
            ->whereHas('journal', fn ($q) => $q->whereIn('status', self::LEDGER_STATUSES)
                ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('journal_date', '>=', $v))
                ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('journal_date', '<=', $v)))
            ->when($filters['account_id'] ?? null, fn ($q, $v) => $q->where('account_id', $v))
            ->with(['journal:id,journal_number,journal_date', 'account:id,account_code,account_name'])
            ->paginate(min((int) ($filters['per_page'] ?? 50), (int) config('zakat.pagination.max_per_page')));
    }

    /**
     * F-12 dan F-13 — penjumlahan dilakukan database, bukan di memori PHP,
     * sehingga tidak ada lagi konversi float atas nilai uang.
     *
     * @return array<int, array<string, mixed>>
     */
    public function trialBalance(array $filters): array
    {
        // Penyaringan organisasi tetap lewat relasi `journal` supaya global scope
        // pada JournalEntry ikut terpakai. Join langsung ke journal_entries akan
        // melewati scope itu dan membocorkan data organisasi lain.
        return JournalLine::query()
            ->whereHas('journal', fn ($q) => $q->whereIn('status', self::LEDGER_STATUSES)
                ->when($filters['accounting_period_id'] ?? null, fn ($q, $v) => $q->where('accounting_period_id', $v)))
            ->join('chart_of_accounts as a', 'a.id', '=', 'journal_lines.account_id')
            ->groupBy('a.id', 'a.account_code', 'a.account_name')
            ->orderBy('a.account_code')
            ->selectRaw('a.id as account_id, a.account_code, a.account_name, sum(journal_lines.debit_amount)::text as debit_total, sum(journal_lines.credit_amount)::text as credit_total')
            ->get()
            ->map(fn ($row) => [
                'account_id' => $row->account_id,
                'account_code' => $row->account_code,
                'account_name' => $row->account_name,
                'debit_total' => $row->debit_total,
                'credit_total' => $row->credit_total,
            ])
            ->all();
    }

    /**
     * F-13 — Collection::sum() menjumlahkan sebagai float, sehingga justru
     * pengecekan keseimbangan jurnal rawan presisi pada nominal besar.
     */
    private function assertBalanced(JournalEntry $journal): void
    {
        $journal->load('lines');

        $debit = $journal->lines->reduce(fn (string $carry, $line) => bcadd($carry, (string) $line->debit_amount, 2), '0.00');
        $credit = $journal->lines->reduce(fn (string $carry, $line) => bcadd($carry, (string) $line->credit_amount, 2), '0.00');

        if (bccomp($debit, $credit, 2) !== 0) {
            throw ZakatException::conflict('JOURNAL_NOT_BALANCED');
        }
    }
}
