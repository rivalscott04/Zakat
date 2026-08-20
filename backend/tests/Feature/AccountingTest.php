<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingTest extends TestCase
{
    use RefreshDatabase;

    public function test_journal_double_entry_dan_posting_immutable(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization);
        $this->loginAs($admin, $organization);
        $cash = $this->postJson('/api/v1/accounting/accounts', ['account_code' => '1100', 'account_name' => 'Cash', 'account_type' => 'asset', 'normal_balance' => 'debit'])->assertCreated()->json('data.id');
        $fund = $this->postJson('/api/v1/accounting/accounts', ['account_code' => '4000', 'account_name' => 'Fund Revenue', 'account_type' => 'revenue', 'normal_balance' => 'credit'])->assertCreated()->json('data.id');
        $period = $this->postJson('/api/v1/accounting/periods', ['period_code' => '202608', 'name' => 'Agustus 2026', 'start_date' => '2026-08-01', 'end_date' => '2026-08-31'])->assertCreated()->json('data.id');
        $unbalanced = $this->postJson('/api/v1/accounting/journals', ['accounting_period_id' => $period, 'journal_date' => '2026-08-20', 'description' => 'Invalid journal', 'lines' => [['account_id' => $cash, 'debit_amount' => 100], ['account_id' => $fund, 'credit_amount' => 50]]])->assertStatus(409);
        $this->assertSame('CONFLICT', $unbalanced->json('code'));
        $journal = $this->postJson('/api/v1/accounting/journals', ['accounting_period_id' => $period, 'journal_date' => '2026-08-20', 'description' => 'Collection inflow', 'lines' => [['account_id' => $cash, 'debit_amount' => 1000000], ['account_id' => $fund, 'credit_amount' => 1000000]]])->assertCreated()->json('data.id');
        $this->postJson("/api/v1/accounting/journals/{$journal}/submit")->assertOk()->assertJsonPath('data.status', 'pending_approval');
        $this->postJson("/api/v1/accounting/journals/{$journal}/approve")->assertOk()->assertJsonPath('data.status', 'approved');
        $this->postJson("/api/v1/accounting/journals/{$journal}/post")->assertOk()->assertJsonPath('data.status', 'posted');
        $this->postJson("/api/v1/accounting/journals/{$journal}/post")->assertStatus(409);
    }
}
