<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountingTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{maker: User, checker: User, organization: Organization, period: string, cash: string, revenue: string} */
    private function ledger(): array
    {
        $organization = $this->organization();
        $maker = $this->member($organization);
        $checker = $this->member($organization, 'ADMIN', ['email' => 'checker'.uniqid().'@example.test']);

        $this->loginAs($maker, $organization);
        $cash = $this->postJson('/api/v1/accounting/accounts', ['account_code' => '1100', 'account_name' => 'Cash', 'account_type' => 'asset', 'normal_balance' => 'debit'])->assertCreated()->json('data.id');
        $revenue = $this->postJson('/api/v1/accounting/accounts', ['account_code' => '4000', 'account_name' => 'Fund Revenue', 'account_type' => 'revenue', 'normal_balance' => 'credit'])->assertCreated()->json('data.id');
        $period = $this->postJson('/api/v1/accounting/periods', ['period_code' => '202608', 'name' => 'Agustus 2026', 'start_date' => '2026-08-01', 'end_date' => '2026-08-31'])->assertCreated()->json('data.id');

        return compact('maker', 'checker', 'organization', 'period', 'cash', 'revenue');
    }

    /** @return array<int, array<string, mixed>> */
    private function lines(array $ledger, string $amount = '1000000'): array
    {
        return [
            ['account_id' => $ledger['cash'], 'debit_amount' => $amount],
            ['account_id' => $ledger['revenue'], 'credit_amount' => $amount],
        ];
    }

    private function journal(array $ledger, string $description = 'Collection inflow'): string
    {
        return $this->postJson('/api/v1/accounting/journals', [
            'accounting_period_id' => $ledger['period'],
            'journal_date' => '2026-08-20',
            'description' => $description,
            'lines' => $this->lines($ledger),
        ])->assertCreated()->json('data.id');
    }

    public function test_journal_double_entry_dan_posting_immutable(): void
    {
        $ledger = $this->ledger();

        $unbalanced = $this->postJson('/api/v1/accounting/journals', [
            'accounting_period_id' => $ledger['period'],
            'journal_date' => '2026-08-20',
            'description' => 'Invalid journal',
            'lines' => [
                ['account_id' => $ledger['cash'], 'debit_amount' => 100],
                ['account_id' => $ledger['revenue'], 'credit_amount' => 50],
            ],
        ])->assertStatus(409);
        $this->assertSame('CONFLICT', $unbalanced->json('code'));

        $journal = $this->journal($ledger);
        $this->postJson("/api/v1/accounting/journals/{$journal}/submit")->assertOk()->assertJsonPath('data.status', 'pending_approval');

        $this->loginAs($ledger['checker'], $ledger['organization']);
        $this->postJson("/api/v1/accounting/journals/{$journal}/approve")->assertOk()->assertJsonPath('data.status', 'approved');
        $this->postJson("/api/v1/accounting/journals/{$journal}/post")->assertOk()->assertJsonPath('data.status', 'posted');
        $this->postJson("/api/v1/accounting/journals/{$journal}/post")->assertStatus(409);
    }

    /** F-17 — pembuat jurnal tidak boleh menyetujui jurnalnya sendiri. */
    public function test_maker_tidak_dapat_menyetujui_jurnal_sendiri(): void
    {
        $ledger = $this->ledger();
        $journal = $this->journal($ledger);

        $this->postJson("/api/v1/accounting/journals/{$journal}/submit")->assertOk();
        $this->postJson("/api/v1/accounting/journals/{$journal}/approve")
            ->assertStatus(403)
            ->assertJsonPath('code', 'FORBIDDEN');

        $this->assertDatabaseHas('journal_entries', ['id' => $journal, 'created_by' => $ledger['maker']->getKey()]);
    }

    /** F-02 — draft tidak boleh langsung diposting, approval tidak boleh terlewati. */
    public function test_jurnal_draft_tidak_dapat_langsung_diposting(): void
    {
        $ledger = $this->ledger();
        $journal = $this->journal($ledger);

        $this->postJson("/api/v1/accounting/journals/{$journal}/post")
            ->assertStatus(409)
            ->assertJsonPath('code', 'INVALID_STATE_TRANSITION');

        $this->postJson("/api/v1/accounting/journals/{$journal}/submit")->assertOk();
        $this->postJson("/api/v1/accounting/journals/{$journal}/post")->assertStatus(409);

        $this->assertDatabaseHas('journal_entries', ['id' => $journal, 'status' => 'pending_approval']);
    }

    /** F-03 — period locked tertutup untuk pencatatan dan posting baru. */
    public function test_period_locked_menolak_jurnal_baru_dan_posting(): void
    {
        $ledger = $this->ledger();

        $journal = $this->journal($ledger);
        $this->postJson("/api/v1/accounting/journals/{$journal}/submit")->assertOk();

        $this->loginAs($ledger['checker'], $ledger['organization']);
        $this->postJson("/api/v1/accounting/journals/{$journal}/approve")->assertOk();
        $this->postJson("/api/v1/accounting/periods/{$ledger['period']}/lock")->assertOk()->assertJsonPath('data.status', 'locked');

        $this->postJson('/api/v1/accounting/journals', [
            'accounting_period_id' => $ledger['period'],
            'journal_date' => '2026-08-21',
            'description' => 'Setelah dikunci',
            'lines' => $this->lines($ledger),
        ])->assertStatus(409)->assertJsonPath('code', 'INVALID_STATE_TRANSITION');

        $this->postJson("/api/v1/accounting/journals/{$journal}/post")
            ->assertStatus(409)
            ->assertJsonPath('code', 'INVALID_STATE_TRANSITION');

        $this->assertDatabaseHas('journal_entries', ['id' => $journal, 'status' => 'approved']);
    }

    /** F-10 — reversal dan penandaan jurnal asal terjadi sebagai satu satuan. */
    public function test_reversal_menandai_jurnal_asal_dan_berimbang_terbalik(): void
    {
        $ledger = $this->ledger();
        $journal = $this->journal($ledger);
        $this->postJson("/api/v1/accounting/journals/{$journal}/submit")->assertOk();

        $this->loginAs($ledger['checker'], $ledger['organization']);
        $this->postJson("/api/v1/accounting/journals/{$journal}/approve")->assertOk();
        $this->postJson("/api/v1/accounting/journals/{$journal}/post")->assertOk();

        $this->postJson("/api/v1/accounting/journals/{$journal}/reverse", ['reason' => 'Salah akun'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'posted');

        $this->assertDatabaseHas('journal_entries', ['id' => $journal, 'status' => 'reversed']);

        // Setelah reversal, seluruh akun kembali nol pada trial balance.
        $balance = $this->getJson('/api/v1/accounting/trial-balance')->assertOk()->json('data');

        foreach ($balance as $row) {
            $this->assertSame(0, bccomp($row['debit_total'], $row['credit_total'], 2), "Akun {$row['account_code']} tidak kembali seimbang.");
        }
    }

    /** F-11 — accounting event duplikat tidak boleh berakhir jadi 500. */
    public function test_accounting_event_duplikat_tidak_menghasilkan_error_server(): void
    {
        $ledger = $this->ledger();
        $payload = [
            'event_type' => 'DISTRIBUTIONCOMPLETED',
            'source_type' => 'distribution',
            'source_id' => (string) Str::ulid(),
            'event_date' => '2026-08-20',
        ];

        $first = $this->postJson('/api/v1/accounting/events', $payload)->assertCreated()->json('data.id');
        $this->postJson('/api/v1/accounting/events', $payload)->assertSuccessful()->assertJsonPath('data.id', $first);

        $this->assertDatabaseCount('accounting_events', 1);
    }

    /** F-12 — general ledger berpaginasi, bukan menarik seluruh baris ke memori. */
    public function test_general_ledger_berpaginasi(): void
    {
        $ledger = $this->ledger();
        $journal = $this->journal($ledger);
        $this->postJson("/api/v1/accounting/journals/{$journal}/submit")->assertOk();

        $this->loginAs($ledger['checker'], $ledger['organization']);
        $this->postJson("/api/v1/accounting/journals/{$journal}/approve")->assertOk();
        $this->postJson("/api/v1/accounting/journals/{$journal}/post")->assertOk();

        $this->getJson('/api/v1/accounting/general-ledger?per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonStructure(['data' => [['journal_number', 'account_code', 'debit_amount', 'credit_amount']], 'meta' => ['current_page', 'per_page', 'total']]);
    }
}
