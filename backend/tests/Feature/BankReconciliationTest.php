<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Organization;
use App\Models\ReconciliationTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/** PRD 14 — import, pencocokan, adjustment, dan penutupan sesi. */
class BankReconciliationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{organization: Organization, maker: User, checker: User, account: string} */
    private function scenario(): array
    {
        $organization = $this->organization();
        $maker = $this->member($organization);
        $checker = $this->member($organization, 'ADMIN', ['email' => 'checker'.uniqid().'@example.test']);

        $this->loginAs($maker, $organization);

        $account = $this->postJson('/api/v1/bank-accounts', [
            'bank_name' => 'Bank Uji',
            'account_name' => 'Rekening Operasional',
            'account_number' => '1234567890',
        ])->assertCreated()->json('data.id');

        return compact('organization', 'maker', 'checker', 'account');
    }

    private function csv(string $rows): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('mutasi.csv', "transaction_date,description,debit,credit,reference\n".$rows);
    }

    private function import(array $s, string $rows, array $overrides = []): array
    {
        return $this->post('/api/v1/bank-statements/import', $overrides + [
            'bank_account_id' => $s['account'],
            'file' => $this->csv($rows),
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'opening_balance' => 0,
            'closing_balance' => 1000000,
        ], ['Accept' => 'application/json'])->assertCreated()->json('data');
    }

    // ---------------------------------------------------------------- import

    public function test_import_csv_membuat_transaksi_dan_menandai_duplikat(): void
    {
        $s = $this->scenario();

        $statement = $this->import($s, "2026-08-05,Setoran zakat,0,1000000,TRX-1\n2026-08-05,Setoran zakat,0,1000000,TRX-2\n");

        $this->assertSame(2, $statement['transaction_count']);
        $this->assertDatabaseHas('bank_transactions', ['transaction_reference' => 'TRX-1', 'duplicate_status' => 'NEW', 'credit_amount' => '1000000.00']);
        // PRD 14G §19 — baris kedua dengan tanggal dan nominal sama ditandai, tidak dibuang.
        $this->assertDatabaseHas('bank_transactions', ['transaction_reference' => 'TRX-2', 'duplicate_status' => 'POSSIBLE_DUPLICATE']);
    }

    /** Core PRD §12 — nominal tidak boleh melewati float. */
    public function test_nominal_besar_tidak_kehilangan_presisi(): void
    {
        $s = $this->scenario();

        $this->import($s, "2026-08-06,Setoran besar,0,12345678901234.56,TRX-BESAR\n");

        $this->assertDatabaseHas('bank_transactions', [
            'transaction_reference' => 'TRX-BESAR',
            'credit_amount' => '12345678901234.56',
        ]);
    }

    public function test_berkas_selain_csv_dan_xlsx_ditolak(): void
    {
        $s = $this->scenario();

        $this->post('/api/v1/bank-statements/import', [
            'bank_account_id' => $s['account'],
            'file' => UploadedFile::fake()->createWithContent('mutasi.php', '<?php echo "x";'),
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
        ], ['Accept' => 'application/json'])->assertStatus(422);
    }

    // -------------------------------------------------------------- matching

    public function test_auto_match_mencocokkan_transaksi_internal_hasil_sinkronisasi(): void
    {
        $s = $this->scenario();
        $this->import($s, "2026-08-05,Setoran zakat,0,1000000,TRX-1\n");

        // Sisi internal: sebelumnya tidak pernah terisi sehingga auto match mustahil.
        $this->postJson('/api/v1/reconciliation-transactions', [
            'transaction_reference' => 'TRX-1',
            'transaction_date' => '2026-08-05',
            'amount' => 1000000,
            'direction' => 'INFLOW',
        ])->assertCreated();

        $session = $this->postJson('/api/v1/reconciliation-sessions', [
            'bank_account_id' => $s['account'],
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'closing_balance' => 1000000,
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/reconciliation-sessions/{$session}/auto-match")->assertOk();

        $this->assertDatabaseHas('bank_transactions', ['transaction_reference' => 'TRX-1', 'match_status' => 'MATCHED']);
        $this->assertDatabaseHas('reconciliation_matches', ['match_type' => 'AUTO', 'matched_amount' => '1000000.00']);
    }

    public function test_pencocokan_sebagian_lalu_penuh(): void
    {
        $s = $this->scenario();
        $this->import($s, "2026-08-07,Setoran gabungan,0,1000000,TRX-P\n");

        $bank = BankTransaction::withoutGlobalScopes()->where('transaction_reference', 'TRX-P')->firstOrFail();

        $first = $this->postJson('/api/v1/reconciliation-transactions', ['transaction_reference' => 'INT-1', 'transaction_date' => '2026-08-07', 'amount' => 400000, 'direction' => 'INFLOW'])->assertCreated()->json('data.id');
        $second = $this->postJson('/api/v1/reconciliation-transactions', ['transaction_reference' => 'INT-2', 'transaction_date' => '2026-08-07', 'amount' => 600000, 'direction' => 'INFLOW'])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/bank-transactions/{$bank->id}/match", ['reconciliation_transaction_id' => $first, 'matched_amount' => 400000])->assertOk();
        $this->assertDatabaseHas('bank_transactions', ['id' => $bank->id, 'match_status' => 'PARTIALLY_MATCHED']);

        $this->postJson("/api/v1/bank-transactions/{$bank->id}/match", ['reconciliation_transaction_id' => $second, 'matched_amount' => 600000])->assertOk();
        $this->assertDatabaseHas('bank_transactions', ['id' => $bank->id, 'match_status' => 'MATCHED']);
    }

    public function test_pencocokan_melebihi_nilai_transaksi_ditolak(): void
    {
        $s = $this->scenario();
        $this->import($s, "2026-08-08,Setoran,0,500000,TRX-L\n");

        $bank = BankTransaction::withoutGlobalScopes()->where('transaction_reference', 'TRX-L')->firstOrFail();
        $internal = $this->postJson('/api/v1/reconciliation-transactions', ['transaction_reference' => 'INT-L', 'transaction_date' => '2026-08-08', 'amount' => 900000, 'direction' => 'INFLOW'])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/bank-transactions/{$bank->id}/match", ['reconciliation_transaction_id' => $internal, 'matched_amount' => 900000])
            ->assertStatus(409);
    }

    public function test_transaksi_yang_sudah_dicocokkan_tidak_dapat_dikecualikan(): void
    {
        $s = $this->scenario();
        $this->import($s, "2026-08-09,Setoran,0,300000,TRX-E\n");

        $bank = BankTransaction::withoutGlobalScopes()->where('transaction_reference', 'TRX-E')->firstOrFail();
        $internal = $this->postJson('/api/v1/reconciliation-transactions', ['transaction_reference' => 'INT-E', 'transaction_date' => '2026-08-09', 'amount' => 300000, 'direction' => 'INFLOW'])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/bank-transactions/{$bank->id}/match", ['reconciliation_transaction_id' => $internal])->assertOk();
        $this->postJson("/api/v1/bank-transactions/{$bank->id}/exclude", ['reason' => 'Bukan milik kami'])->assertStatus(409);
    }

    // ------------------------------------------------------------ adjustment

    public function test_adjustment_disetujui_menerbitkan_accounting_event(): void
    {
        $s = $this->scenario();
        $this->import($s, "2026-08-10,Biaya admin,15000,0,TRX-FEE\n");

        $session = $this->postJson('/api/v1/reconciliation-sessions', [
            'bank_account_id' => $s['account'], 'period_start' => '2026-08-01', 'period_end' => '2026-08-31',
        ])->assertCreated()->json('data.id');

        $bank = BankTransaction::withoutGlobalScopes()->where('transaction_reference', 'TRX-FEE')->firstOrFail();

        $adjustment = $this->postJson('/api/v1/reconciliation-adjustments', [
            'reconciliation_session_id' => $session,
            'bank_transaction_id' => $bank->id,
            'adjustment_type' => 'BANK_FEE',
            'amount' => 15000,
            'reason' => 'Biaya administrasi bulanan',
        ])->assertCreated()->json('data.id');

        // Maker checker: pembuat tidak boleh menyetujui usulannya sendiri.
        $this->postJson("/api/v1/reconciliation-adjustments/{$adjustment}/approve")->assertStatus(403);

        $this->loginAs($s['checker'], $s['organization']);
        $this->postJson("/api/v1/reconciliation-adjustments/{$adjustment}/approve")->assertOk();

        // PRD 14T §49 — modul ini menerbitkan event, jurnal dibuat modul Accounting.
        $this->assertDatabaseHas('accounting_events', [
            'event_type' => 'BANKADJUSTMENT',
            'source_type' => 'reconciliation_adjustment',
            'source_id' => $adjustment,
            'status' => 'pending',
        ]);
        $this->assertDatabaseCount('journal_entries', 0);
    }

    // ------------------------------------------------------------------ sesi

    public function test_ringkasan_memakai_rumus_prd_dan_siklus_sesi_dijaga(): void
    {
        $s = $this->scenario();
        $this->import($s, "2026-08-11,Setoran,0,1000000,TRX-S1\n2026-08-12,Tarik,250000,0,TRX-S2\n");

        $session = $this->postJson('/api/v1/reconciliation-sessions', [
            'bank_account_id' => $s['account'],
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'opening_balance' => 0,
            'closing_balance' => 750000,
        ])->assertCreated()->json('data.id');

        // PRD 14P §43: opening + credit - debit = 0 + 1.000.000 - 250.000
        $summary = $this->getJson("/api/v1/reconciliation-sessions/{$session}/summary")->assertOk()->json('data');
        $this->assertSame('750000.00', $summary['expected_closing_balance']);
        $this->assertTrue($summary['balance_valid']);
        $this->assertSame(2, $summary['total_transactions']);

        // Sesi harus selesai sebelum ditutup.
        $this->postJson("/api/v1/reconciliation-sessions/{$session}/close")->assertStatus(409);
        $this->postJson("/api/v1/reconciliation-sessions/{$session}/complete")->assertOk();
        $this->postJson("/api/v1/reconciliation-sessions/{$session}/close")->assertOk();

        // Sesi tertutup tidak menerima perubahan lagi.
        $this->postJson("/api/v1/reconciliation-sessions/{$session}/auto-match")->assertStatus(409);
    }

    // -------------------------------------------------------- keamanan

    /** PRD 14C §8 — nomor rekening tidak pernah keluar utuh. */
    public function test_nomor_rekening_dimask_dan_terenkripsi(): void
    {
        $s = $this->scenario();

        $response = $this->getJson("/api/v1/bank-accounts/{$s['account']}")->assertOk();

        $this->assertStringNotContainsString('1234567890', $response->getContent());
        $this->assertSame('******7890', $response->json('data.account_number_masked'));

        $stored = BankAccount::withoutGlobalScopes()->find($s['account']);
        $this->assertNotSame('1234567890', $stored->getRawOriginal('account_number_encrypted'));
        $this->assertSame('1234567890', $stored->account_number_encrypted);
    }

    public function test_data_organisasi_lain_tidak_dapat_diakses(): void
    {
        $s = $this->scenario();
        $this->import($s, "2026-08-13,Setoran,0,100000,TRX-X\n");

        $organizationB = $this->organization();
        $adminB = $this->member($organizationB, 'ADMIN', ['email' => 'lainbank@example.test']);

        $this->loginAs($adminB, $organizationB);
        $this->getJson("/api/v1/bank-accounts/{$s['account']}")->assertNotFound();
        $this->getJson('/api/v1/bank-transactions')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_permission_kurang_ditolak(): void
    {
        $s = $this->scenario();

        $viewer = $this->member($s['organization'], 'VIEWER', ['email' => 'viewerbank@example.test']);
        $this->loginAs($viewer, $s['organization']);

        $this->postJson('/api/v1/bank-accounts', ['bank_name' => 'X', 'account_name' => 'Y', 'account_number' => '1'])->assertForbidden();
        $this->getJson('/api/v1/bank-transactions')->assertForbidden();
    }

    public function test_sinkronisasi_transaksi_internal_bersifat_idempoten(): void
    {
        $s = $this->scenario();

        // Dua kali sinkronisasi pada rentang yang sama tidak boleh menggandakan.
        $first = $this->postJson('/api/v1/reconciliation-transactions/sync', ['from' => '2026-08-01', 'to' => '2026-08-31'])->assertOk()->json('data');
        $this->postJson('/api/v1/reconciliation-transactions/sync', ['from' => '2026-08-01', 'to' => '2026-08-31'])->assertOk();

        $this->assertSame(
            ReconciliationTransaction::withoutGlobalScopes()->count(),
            array_sum($first),
            'Sinkronisasi kedua tidak boleh menambah baris baru.'
        );
    }
}
