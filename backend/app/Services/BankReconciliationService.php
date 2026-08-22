<?php

namespace App\Services;

use App\Exceptions\ZakatException;
use App\Models\BankAccount;
use App\Models\BankStatement;
use App\Models\BankTransaction;
use App\Models\ReconciliationAdjustment;
use App\Models\ReconciliationMatch;
use App\Models\ReconciliationSession;
use App\Models\ReconciliationTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/** PRD 14 — Bank Reconciliation. */
class BankReconciliationService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly AccountingService $accounting,
    ) {}

    // ------------------------------------------------------------- rekening

    public function accounts(array $filters): LengthAwarePaginator
    {
        return BankAccount::query()
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where(
                fn ($x) => $x->where('bank_name', 'ilike', "%{$v}%")
                    ->orWhere('account_name', 'ilike', "%{$v}%")
                    ->orWhere('account_code', 'ilike', "%{$v}%")
            ))
            ->latest()
            ->paginate($this->perPage($filters));
    }

    public function account(string $id): BankAccount
    {
        return BankAccount::find($id) ?? throw ZakatException::notFound('Rekening bank tidak ditemukan.');
    }

    /** PRD 14C §8 — nomor rekening disimpan terenkripsi, hanya mask yang tampil. */
    public function createAccount(array $data): BankAccount
    {
        $account = new BankAccount;
        $account->fill([
            'bank_name' => $data['bank_name'],
            'account_name' => $data['account_name'],
            'currency' => $data['currency'] ?? 'IDR',
            'opening_balance' => $data['opening_balance'] ?? 0,
            'current_balance' => $data['opening_balance'] ?? 0,
            'status' => 'ACTIVE',
        ]);
        $account->account_number_encrypted = $data['account_number'];
        $account->account_number_masked = BankAccount::mask($data['account_number']);
        $account->save();

        $this->audit->record('bank_account_created', $account, context: ['account_number_masked' => $account->account_number_masked]);

        return $account;
    }

    // --------------------------------------------------------------- import

    /** PRD 14F §17 dan §18. */
    public function import(BankAccount $account, UploadedFile $file, array $data): BankStatement
    {
        $rows = $this->rows($file);

        if ($rows === []) {
            throw ZakatException::conflict('File mutasi tidak memiliki transaksi.');
        }

        return DB::transaction(function () use ($account, $rows, $data, $file) {
            $statement = BankStatement::create([
                'bank_account_id' => $account->id,
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'opening_balance' => $data['opening_balance'] ?? 0,
                'closing_balance' => $data['closing_balance'] ?? 0,
                'status' => 'IMPORTED',
                'imported_by' => auth()->id(),
                'imported_at' => now(),
            ]);

            foreach ($rows as $row) {
                $this->importRow($account, $statement, $row, $data);
            }

            $statement->forceFill(['transaction_count' => $statement->transactions()->count()])->saveQuietly();
            $this->audit->record('bank_statement_imported', $statement, context: ['filename' => $file->getClientOriginalName()]);

            return $statement->load('transactions');
        });
    }

    private function importRow(BankAccount $account, BankStatement $statement, array $row, array $data): void
    {
        $date = $row[$data['date_column'] ?? 'transaction_date'] ?? null;

        if (blank($date)) {
            return;
        }

        // Core PRD §12 — nominal tidak pernah melewati float.
        $debit = $this->money($row[$data['debit_column'] ?? 'debit'] ?? 0);
        $credit = $this->money($row[$data['credit_column'] ?? 'credit'] ?? 0);

        $reference = (string) ($row[$data['reference_column'] ?? 'reference'] ?? '');

        if ($reference === '') {
            $reference = 'BTX'.now()->format('YmdHis').random_int(100, 999);
        }

        // PRD 14G §19 — kandidat duplikat ditandai, tidak dibuang, supaya petugas
        // yang memutuskan.
        $duplicate = BankTransaction::query()
            ->where('bank_account_id', $account->id)
            ->whereDate('transaction_date', $date)
            ->where('debit_amount', $debit)
            ->where('credit_amount', $credit)
            ->exists();

        $transaction = BankTransaction::create([
            'bank_statement_id' => $statement->id,
            'bank_account_id' => $account->id,
            'transaction_reference' => $reference,
            'transaction_date' => $date,
            'description' => $row[$data['description_column'] ?? 'description'] ?? null,
            'debit_amount' => $debit,
            'credit_amount' => $credit,
            'balance' => isset($row[$data['balance_column'] ?? 'balance']) ? $this->money($row[$data['balance_column'] ?? 'balance']) : null,
            'currency' => $account->currency,
            'raw_data' => $row,
            'match_status' => 'UNMATCHED',
            'duplicate_status' => $duplicate ? 'POSSIBLE_DUPLICATE' : 'NEW',
        ]);

        if ($duplicate) {
            $this->audit->record('bank_transaction_duplicate_detected', $transaction);
        }
    }

    // ------------------------------------------------------------- matching

    public function transactions(array $filters): LengthAwarePaginator
    {
        return BankTransaction::query()
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('match_status', $v))
            ->when($filters['bank_account_id'] ?? null, fn ($q, $v) => $q->where('bank_account_id', $v))
            ->latest('transaction_date')
            ->paginate($this->perPage($filters));
    }

    /** PRD 14J §28 — pencocokan otomatis berdasarkan referensi, nominal, dan tanggal. */
    public function autoMatch(ReconciliationSession $session): ReconciliationSession
    {
        $this->assertOpen($session);

        $matched = 0;

        $candidates = BankTransaction::query()
            ->where('bank_account_id', $session->bank_account_id)
            ->whereBetween('transaction_date', [$session->period_start, $session->period_end])
            ->where('match_status', 'UNMATCHED')
            ->get();

        foreach ($candidates as $transaction) {
            $amount = $this->transactionAmount($transaction);

            $internal = ReconciliationTransaction::query()
                ->where('status', 'UNMATCHED')
                ->where('transaction_reference', $transaction->transaction_reference)
                ->where('amount', $amount)
                ->whereDate('transaction_date', $transaction->transaction_date)
                ->first();

            if ($internal === null) {
                continue;
            }

            $this->createMatch($transaction, $internal, $amount, 'AUTO');
            $matched++;
        }

        $session->forceFill(['status' => 'IN_PROGRESS', 'started_by' => $session->started_by ?? auth()->id(), 'started_at' => $session->started_at ?? now()])->saveQuietly();
        $this->audit->record('reconciliation_auto_match_completed', $session, context: ['matched_count' => $matched]);

        return $this->refreshSummary($session);
    }

    /** PRD 14K dan 14L — pencocokan manual, boleh sebagian. */
    public function match(BankTransaction $transaction, array $data): BankTransaction
    {
        $internal = ReconciliationTransaction::find($data['reconciliation_transaction_id'])
            ?? throw ZakatException::notFound('Transaksi internal tidak ditemukan.');

        if ($transaction->match_status === 'EXCLUDED') {
            throw ZakatException::invalidTransition('Transaksi yang dikecualikan tidak dapat dicocokkan.');
        }

        $total = $this->transactionAmount($transaction);
        $already = (string) $transaction->matches()->sum('matched_amount');
        $amount = (string) ($data['matched_amount'] ?? bcsub($total, $already, 2));

        if (bccomp($amount, '0', 2) <= 0) {
            throw ZakatException::conflict('Nilai pencocokan harus lebih besar dari nol.');
        }

        if (bccomp(bcadd($already, $amount, 2), $total, 2) > 0) {
            throw ZakatException::conflict('Nilai pencocokan melebihi nilai transaksi bank.');
        }

        $partial = bccomp(bcadd($already, $amount, 2), $total, 2) < 0;

        $this->createMatch($transaction, $internal, $amount, $partial ? 'PARTIAL' : 'MANUAL');

        $this->audit->record($partial ? 'bank_transaction_partial_matched' : 'bank_transaction_matched', $transaction);

        return $transaction->fresh()->load('matches');
    }

    /** PRD 14R §46. */
    public function exclude(BankTransaction $transaction, string $reason): BankTransaction
    {
        if ($transaction->matches()->exists()) {
            throw ZakatException::conflict('Transaksi yang sudah dicocokkan tidak dapat dikecualikan.');
        }

        $transaction->forceFill(['match_status' => 'EXCLUDED'])->saveQuietly();
        $this->audit->record('bank_transaction_excluded', $transaction, context: ['reason' => $reason]);

        return $transaction;
    }

    // -------------------------------------------------------------- adjustment

    /** PRD 14S §47. */
    public function createAdjustment(array $data): ReconciliationAdjustment
    {
        $session = ReconciliationSession::find($data['reconciliation_session_id'])
            ?? throw ZakatException::notFound('Sesi rekonsiliasi tidak ditemukan.');

        $this->assertOpen($session);

        $adjustment = new ReconciliationAdjustment;
        $adjustment->fill(collect($data)->only(['reconciliation_session_id', 'bank_transaction_id', 'adjustment_type', 'amount', 'reason', 'reference'])->all());
        $adjustment->organization_id = $session->organization_id;
        $adjustment->status = 'PENDING';
        $adjustment->created_by = auth()->id();
        $adjustment->save();

        $this->audit->record('reconciliation_adjustment_created', $adjustment);

        return $adjustment;
    }

    /**
     * PRD 14T §49 — modul ini tidak membuat jurnal sendiri.
     *
     * Persetujuan adjustment hanya menerbitkan accounting event; modul Accounting
     * yang menentukan dan membuat journal entry-nya.
     */
    public function approveAdjustment(ReconciliationAdjustment $adjustment): ReconciliationAdjustment
    {
        $this->assertAdjustmentPending($adjustment);

        // Maker checker: pembuat tidak boleh menyetujui usulannya sendiri.
        if ($adjustment->created_by !== null && $adjustment->created_by === auth()->id()) {
            throw ZakatException::forbidden('Pembuat adjustment tidak dapat menyetujui usulannya sendiri.');
        }

        return DB::transaction(function () use ($adjustment) {
            $adjustment->forceFill(['status' => 'APPROVED', 'approved_by' => auth()->id()])->saveQuietly();

            $this->accounting->event([
                'event_type' => 'BANKADJUSTMENT',
                'source_type' => 'reconciliation_adjustment',
                'source_id' => $adjustment->id,
                'reference_number' => $adjustment->reference,
                'event_date' => today(),
                'payload' => [
                    'adjustment_type' => $adjustment->adjustment_type,
                    'amount' => (string) $adjustment->amount,
                    'reason' => $adjustment->reason,
                    'reconciliation_session_id' => $adjustment->reconciliation_session_id,
                    'bank_transaction_id' => $adjustment->bank_transaction_id,
                ],
            ]);

            $this->audit->record('reconciliation_adjustment_approved', $adjustment);

            return $adjustment;
        });
    }

    public function rejectAdjustment(ReconciliationAdjustment $adjustment, string $reason): ReconciliationAdjustment
    {
        $this->assertAdjustmentPending($adjustment);

        $adjustment->forceFill(['status' => 'REJECTED', 'approved_by' => auth()->id()])->saveQuietly();
        $this->audit->record('reconciliation_adjustment_rejected', $adjustment, context: ['reason' => $reason]);

        return $adjustment;
    }

    // ------------------------------------------------------------------ sesi

    public function sessions(array $filters): LengthAwarePaginator
    {
        return ReconciliationSession::query()->latest()->paginate($this->perPage($filters));
    }

    public function session(string $id): ReconciliationSession
    {
        return ReconciliationSession::find($id) ?? throw ZakatException::notFound('Sesi rekonsiliasi tidak ditemukan.');
    }

    public function createSession(array $data): ReconciliationSession
    {
        $session = ReconciliationSession::create($data + [
            'status' => 'DRAFT',
            'opening_balance' => $data['opening_balance'] ?? 0,
            'closing_balance' => $data['closing_balance'] ?? 0,
        ]);

        $this->audit->record('reconciliation_session_created', $session);

        return $session;
    }

    public function complete(ReconciliationSession $session): ReconciliationSession
    {
        if (in_array($session->status, ['COMPLETED', 'CLOSED'], true)) {
            throw ZakatException::invalidTransition('Sesi sudah selesai.');
        }

        $session = $this->refreshSummary($session);
        $session->forceFill(['status' => 'COMPLETED', 'completed_at' => now()])->saveQuietly();
        $this->audit->record('reconciliation_completed', $session);

        return $session->fresh();
    }

    public function close(ReconciliationSession $session): ReconciliationSession
    {
        if ($session->status !== 'COMPLETED') {
            throw ZakatException::invalidTransition('Sesi harus selesai sebelum ditutup.');
        }

        $session->forceFill(['status' => 'CLOSED'])->saveQuietly();
        $this->audit->record('reconciliation_closed', $session);

        return $session;
    }

    /** PRD 14P §42 dan §43. */
    public function summary(ReconciliationSession $session): array
    {
        $scope = fn () => BankTransaction::query()
            ->where('bank_account_id', $session->bank_account_id)
            ->whereBetween('transaction_date', [$session->period_start, $session->period_end]);

        $credit = (string) $scope()->sum('credit_amount');
        $debit = (string) $scope()->sum('debit_amount');
        $expected = bcsub(bcadd((string) $session->opening_balance, $credit, 2), $debit, 2);

        return [
            'opening_balance' => (string) $session->opening_balance,
            'total_credit' => $credit,
            'total_debit' => $debit,
            'closing_balance' => (string) $session->closing_balance,
            'expected_closing_balance' => $expected,
            'difference_amount' => bcsub($expected, (string) $session->closing_balance, 2),
            'balance_valid' => bccomp($expected, (string) $session->closing_balance, 2) === 0,
            'total_transactions' => $scope()->count(),
            'matched' => (clone $scope())->where('match_status', 'MATCHED')->count(),
            'partially_matched' => (clone $scope())->where('match_status', 'PARTIALLY_MATCHED')->count(),
            'unmatched' => (clone $scope())->where('match_status', 'UNMATCHED')->count(),
            'excluded' => (clone $scope())->where('match_status', 'EXCLUDED')->count(),
            'possible_duplicates' => (clone $scope())->where('duplicate_status', 'POSSIBLE_DUPLICATE')->count(),
        ];
    }

    // --------------------------------------------------------------- helpers

    private function createMatch(BankTransaction $transaction, ReconciliationTransaction $internal, string $amount, string $type): void
    {
        DB::transaction(function () use ($transaction, $internal, $amount, $type) {
            ReconciliationMatch::create([
                'bank_transaction_id' => $transaction->id,
                'reconciliation_transaction_id' => $internal->id,
                'match_type' => $type,
                'matched_amount' => $amount,
                'confidence_score' => $type === 'AUTO' ? 100 : 100,
                'matched_by' => auth()->id(),
                'matched_at' => now(),
                'status' => 'MATCHED',
            ]);

            $total = $this->transactionAmount($transaction);
            $already = (string) $transaction->matches()->sum('matched_amount');
            $partial = bccomp($already, $total, 2) < 0;

            $transaction->forceFill(['match_status' => $partial ? 'PARTIALLY_MATCHED' : 'MATCHED'])->saveQuietly();
            $internal->forceFill(['status' => $partial ? 'PARTIALLY_MATCHED' : 'MATCHED'])->saveQuietly();
        });
    }

    private function refreshSummary(ReconciliationSession $session): ReconciliationSession
    {
        $summary = $this->summary($session);

        $matched = (string) ReconciliationMatch::query()
            ->whereIn('bank_transaction_id', BankTransaction::query()
                ->where('bank_account_id', $session->bank_account_id)
                ->whereBetween('transaction_date', [$session->period_start, $session->period_end])
                ->select('id'))
            ->sum('matched_amount');

        $movement = bcadd($summary['total_credit'], $summary['total_debit'], 2);

        $session->forceFill([
            'matched_amount' => $matched,
            'unmatched_amount' => bcsub($movement, $matched, 2),
            'difference_amount' => $summary['difference_amount'],
        ])->saveQuietly();

        return $session->fresh();
    }

    private function transactionAmount(BankTransaction $transaction): string
    {
        return bcadd((string) $transaction->debit_amount, (string) $transaction->credit_amount, 2);
    }

    private function money(mixed $value): string
    {
        $clean = preg_replace('/[^0-9.\-]/', '', (string) $value) ?? '0';

        return $clean === '' || $clean === '-' ? '0.00' : bcadd($clean, '0', 2);
    }

    private function assertOpen(ReconciliationSession $session): void
    {
        if (in_array($session->status, ['COMPLETED', 'CLOSED'], true)) {
            throw ZakatException::invalidTransition('Sesi yang sudah selesai tidak dapat diubah.');
        }
    }

    private function assertAdjustmentPending(ReconciliationAdjustment $adjustment): void
    {
        if ($adjustment->status !== 'PENDING') {
            throw ZakatException::invalidTransition('Adjustment tidak lagi menunggu keputusan.');
        }
    }

    /** PRD 14F §16 — hanya CSV dan XLSX. */
    private function rows(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return $extension === 'csv' ? $this->csvRows($file) : $this->xlsxRows($file);
    }

    private function csvRows(UploadedFile $file): array
    {
        $lines = file($file->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $header = null;
        $rows = [];

        foreach ($lines as $line) {
            $cells = str_getcsv($line);

            if ($header === null) {
                $header = array_map(fn ($v) => trim((string) $v), $cells);

                continue;
            }

            if (count($header) === count($cells)) {
                $rows[] = array_combine($header, $cells);
            }
        }

        return $rows;
    }

    private function xlsxRows(UploadedFile $file): array
    {
        if (! class_exists('ZipArchive')) {
            throw ZakatException::conflict('Server belum mendukung pembacaan XLSX.');
        }

        $zip = new \ZipArchive;

        if ($zip->open($file->getRealPath()) !== true) {
            throw ZakatException::conflict('File XLSX rusak.');
        }

        $shared = [];

        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $parsed = simplexml_load_string($xml);

            foreach ($parsed->si as $item) {
                $shared[] = (string) ($item->t ?? implode('', array_map('strval', (array) ($item->r->t ?? []))));
            }
        }

        $sheet = simplexml_load_string((string) $zip->getFromName('xl/worksheets/sheet1.xml'));
        $zip->close();

        $rows = [];
        $header = [];

        foreach ($sheet->sheetData->row as $row) {
            $values = [];

            foreach ($row->c as $cell) {
                $value = (string) $cell->v;

                if ((string) $cell['t'] === 's') {
                    $value = $shared[(int) $value] ?? $value;
                }

                $values[] = $value;
            }

            if ($header === []) {
                $header = array_map('trim', $values);

                continue;
            }

            if (count($header) === count($values)) {
                $rows[] = array_combine($header, $values);
            }
        }

        return $rows;
    }

    private function perPage(array $filters): int
    {
        return min((int) ($filters['per_page'] ?? 25), (int) config('zakat.pagination.max_per_page'));
    }
}
