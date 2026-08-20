<?php

namespace Tests\Feature;

use App\Models\Distribution;
use App\Models\Fund;
use App\Models\Mustahik;
use App\Models\Organization;
use App\Models\User;
use App\Services\DistributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PRD 12AF — pengujian lifecycle, integrasi dana, dan keamanan distribution. */
class DistributionTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{maker: User, checker: User, organization: Organization, mustahik: string, fund: string} */
    private function scenario(string $openingBalance = '10000000'): array
    {
        $organization = $this->organization();
        $maker = $this->member($organization);
        $checker = $this->member($organization, 'ADMIN', ['email' => 'checker'.uniqid().'@example.test']);

        $this->loginAs($maker, $organization);
        $mustahik = $this->postJson('/api/v1/mustahiks', ['full_name' => 'Penerima Uji'])->assertCreated()->json('data.id');
        $fund = $this->postJson('/api/v1/funds', [
            'fund_code' => 'DST'.strtoupper(substr(uniqid(), -6)),
            'name' => 'Fund Uji',
            'fund_type' => 'zakat',
            'opening_balance' => $openingBalance,
        ])->assertCreated()->json('data.id');

        return compact('maker', 'checker', 'organization', 'mustahik', 'fund');
    }

    private function draft(array $scenario, array $overrides = []): string
    {
        return $this->postJson('/api/v1/distributions', $overrides + [
            'mustahik_id' => $scenario['mustahik'],
            'fund_id' => $scenario['fund'],
            'distribution_type' => 'cash',
            'requested_amount' => 2000000,
            'description' => 'Bantuan konsumtif',
        ])->assertCreated()->json('data.id');
    }

    /** Maker membuat, checker menyetujui, lalu maker merealisasikan. */
    private function untilProcessing(array $scenario, array $overrides = []): string
    {
        $this->loginAs($scenario['maker'], $scenario['organization']);
        $id = $this->draft($scenario, $overrides);
        $this->postJson("/api/v1/distributions/{$id}/submit")->assertOk();

        $this->loginAs($scenario['checker'], $scenario['organization']);
        $this->postJson("/api/v1/distributions/{$id}/approve")->assertOk();
        $this->postJson("/api/v1/distributions/{$id}/reserve")->assertOk();
        $this->postJson("/api/v1/distributions/{$id}/process")->assertOk();

        return $id;
    }

    // -------------------------------------------------------------- lifecycle

    public function test_alur_penuh_sampai_completed_menerbitkan_accounting_event(): void
    {
        $scenario = $this->scenario();
        $id = $this->untilProcessing($scenario);

        $this->postJson("/api/v1/distributions/{$id}/complete", ['amount' => 2000000])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.remaining_amount', '0.00');

        $this->assertDatabaseHas('fund_movements', ['fund_id' => $scenario['fund'], 'movement_type' => 'distribution', 'direction' => 'out']);
        $this->assertDatabaseHas('distribution_cash_details', ['distribution_id' => $id, 'amount' => '2000000.00']);

        // PRD 12X §56 — Distribution hanya menerbitkan event, bukan jurnal.
        $this->assertDatabaseHas('accounting_events', [
            'event_type' => 'DISTRIBUTIONCOMPLETED',
            'source_type' => 'distribution',
            'source_id' => $id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseCount('journal_entries', 0);

        $this->assertDatabaseHas('distribution_reservations', ['distribution_id' => $id, 'status' => 'consumed']);

        // PRD 12AD §67 — ringkasan status untuk dashboard.
        $this->getJson('/api/v1/distributions/summary')
            ->assertOk()
            ->assertJsonPath('data.by_status.completed.total', 1)
            ->assertJsonPath('data.by_status.completed.distributed_amount', '2000000.00')
            ->assertJsonPath('data.by_status.draft.total', 0);
    }

    public function test_maker_tidak_dapat_menyetujui_distribution_sendiri(): void
    {
        $scenario = $this->scenario();
        $this->loginAs($scenario['maker'], $scenario['organization']);
        $id = $this->draft($scenario);
        $this->postJson("/api/v1/distributions/{$id}/submit")->assertOk();

        $this->postJson("/api/v1/distributions/{$id}/approve")->assertForbidden();
    }

    public function test_transisi_status_yang_tidak_sah_ditolak(): void
    {
        $scenario = $this->scenario();
        $this->loginAs($scenario['maker'], $scenario['organization']);
        $id = $this->draft($scenario);

        // Draft belum boleh langsung reserve atau complete.
        $this->postJson("/api/v1/distributions/{$id}/reserve")->assertStatus(409)->assertJsonPath('code', 'INVALID_STATE_TRANSITION');
        $this->postJson("/api/v1/distributions/{$id}/complete", ['amount' => 1000])->assertStatus(409);
    }

    public function test_realisasi_melebihi_approved_amount_ditolak(): void
    {
        $scenario = $this->scenario();
        $id = $this->untilProcessing($scenario);

        $this->postJson("/api/v1/distributions/{$id}/complete", ['amount' => 2000001])
            ->assertStatus(409)
            ->assertJsonPath('code', 'CONFLICT');
    }

    /** PRD 12O §37. */
    public function test_realisasi_sebagian_menahan_sisa_reservation(): void
    {
        $scenario = $this->scenario();
        $id = $this->untilProcessing($scenario);

        $this->postJson("/api/v1/distributions/{$id}/complete", ['amount' => 800000])
            ->assertOk()
            ->assertJsonPath('data.status', 'partially_completed')
            ->assertJsonPath('data.remaining_amount', '1200000.00');

        // Sisa yang belum disalurkan tetap ditahan agar tidak terpakai distribution lain.
        $fund = Fund::withoutGlobalScopes()->find($scenario['fund']);
        $this->assertSame('1200000.00', (string) $fund->reserved_balance);

        // Accounting event baru terbit setelah realisasi selesai penuh.
        $this->assertDatabaseCount('accounting_events', 0);

        $this->postJson("/api/v1/distributions/{$id}/process")->assertOk();
        $this->postJson("/api/v1/distributions/{$id}/complete", ['amount' => 1200000])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('accounting_events', ['event_type' => 'DISTRIBUTIONCOMPLETED', 'source_id' => $id]);
    }

    public function test_distribution_dapat_menghabiskan_seluruh_saldo_fund(): void
    {
        $scenario = $this->scenario('2000000');
        $id = $this->untilProcessing($scenario);

        // Reservation atas dana sendiri tidak boleh memblokir outflow-nya.
        $this->postJson("/api/v1/distributions/{$id}/complete", ['amount' => 2000000])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $fund = Fund::withoutGlobalScopes()->find($scenario['fund']);
        $this->assertSame('0.00', (string) $fund->current_balance);
    }

    // ------------------------------------------------------- gagal dan batal

    public function test_kegagalan_melepas_reservation_dan_retry_menambah_hitungan(): void
    {
        $scenario = $this->scenario();
        $id = $this->untilProcessing($scenario);

        $this->postJson("/api/v1/distributions/{$id}/fail", ['failure_reason' => 'transfer_failed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.failure_reason', 'transfer_failed');

        $fund = Fund::withoutGlobalScopes()->find($scenario['fund']);
        $this->assertSame('0.00', (string) $fund->reserved_balance);
        $this->assertDatabaseHas('distribution_reservations', ['distribution_id' => $id, 'status' => 'released']);

        $this->postJson("/api/v1/distributions/{$id}/process")
            ->assertOk()
            ->assertJsonPath('data.status', 'processing')
            ->assertJsonPath('data.retry_count', 1);
    }

    public function test_pembatalan_mencatat_alasan_dan_mengembalikan_dana(): void
    {
        $scenario = $this->scenario();
        $id = $this->untilProcessing($scenario);

        $this->postJson("/api/v1/distributions/{$id}/cancel", ['reason' => 'Penerima pindah domisili'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.cancellation_reason', 'Penerima pindah domisili');

        $fund = Fund::withoutGlobalScopes()->find($scenario['fund']);
        $this->assertSame('0.00', (string) $fund->reserved_balance);
        $this->assertSame('10000000.00', (string) $fund->current_balance);

        // PRD 12U — distribution yang sudah dibatalkan bersifat final.
        $this->postJson("/api/v1/distributions/{$id}/process")->assertStatus(409);
    }

    public function test_reversal_mengembalikan_dana_dan_menerbitkan_event_reversal(): void
    {
        $scenario = $this->scenario();
        $id = $this->untilProcessing($scenario);
        $this->postJson("/api/v1/distributions/{$id}/complete", ['amount' => 2000000])->assertOk();

        $this->postJson("/api/v1/distributions/{$id}/reverse", ['reason' => 'Salah penerima'])
            ->assertOk()
            ->assertJsonPath('data.status', 'reversed')
            ->assertJsonPath('data.reversal_reason', 'Salah penerima');

        $fund = Fund::withoutGlobalScopes()->find($scenario['fund']);
        $this->assertSame('10000000.00', (string) $fund->current_balance);

        $this->assertDatabaseHas('accounting_events', ['event_type' => 'DISTRIBUTIONREVERSED', 'source_id' => $id]);
    }

    public function test_accounting_event_tidak_terbit_dua_kali(): void
    {
        $scenario = $this->scenario();
        $id = $this->untilProcessing($scenario);
        $this->postJson("/api/v1/distributions/{$id}/complete", ['amount' => 2000000])->assertOk();

        // Memanggil ulang emitter tidak boleh menambah baris event.
        $distribution = Distribution::withoutGlobalScopes()->findOrFail($id);
        $service = app(DistributionService::class);
        $method = new \ReflectionMethod($service, 'emitAccountingEvent');
        $method->invoke($service, $distribution, 'DISTRIBUTIONCOMPLETED', '2000000.00', []);

        $this->assertDatabaseCount('accounting_events', 1);
    }

    // ----------------------------------------------------- bank transfer dan bukti

    public function test_bank_transfer_wajib_detail_dan_nomor_rekening_dimasking(): void
    {
        $scenario = $this->scenario();
        $id = $this->untilProcessing($scenario, ['distribution_type' => 'bank_transfer']);

        $this->postJson("/api/v1/distributions/{$id}/complete", ['amount' => 2000000])
            ->assertStatus(409);

        $response = $this->postJson("/api/v1/distributions/{$id}/complete", [
            'amount' => 2000000,
            'bank_transfer' => [
                'bank_name' => 'Bank Uji',
                'account_holder_name' => 'Penerima Uji',
                'account_number' => '1234567890',
                'transfer_reference' => 'TRF-001',
            ],
        ])->assertOk();

        $this->assertSame('******7890', $response->json('data.bank_transfers.0.account_number_masked'));
        $this->assertStringNotContainsString('1234567890', $response->getContent());
        $this->assertDatabaseMissing('distribution_bank_transfers', ['account_number_encrypted' => '1234567890']);
    }

    public function test_bukti_dan_konfirmasi_penerima(): void
    {
        $scenario = $this->scenario();
        $id = $this->untilProcessing($scenario);
        $this->postJson("/api/v1/distributions/{$id}/complete", ['amount' => 2000000])->assertOk();

        $proof = $this->postJson("/api/v1/distributions/{$id}/proofs", [
            'proof_type' => 'receipt',
            'reference_number' => 'KW-001',
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/distributions/{$id}/proofs/{$proof}/verify")
            ->assertOk()
            ->assertJsonPath('data.reference_number', 'KW-001');

        $this->postJson("/api/v1/distributions/{$id}/proofs/{$proof}/verify")->assertStatus(409);

        $this->postJson("/api/v1/distributions/{$id}/confirm", ['confirmation_method' => 'signature'])->assertOk();
        $this->postJson("/api/v1/distributions/{$id}/confirm", ['confirmation_method' => 'signature'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'DUPLICATE_RESOURCE');
    }

    // ------------------------------------------------------------- validasi

    public function test_saldo_fund_tidak_cukup_ditolak(): void
    {
        $scenario = $this->scenario('1000000');
        $this->loginAs($scenario['maker'], $scenario['organization']);

        $this->postJson('/api/v1/distributions', [
            'mustahik_id' => $scenario['mustahik'],
            'fund_id' => $scenario['fund'],
            'distribution_type' => 'cash',
            'requested_amount' => 5000000,
        ])->assertStatus(409)->assertJsonPath('code', 'CONFLICT');
    }

    public function test_mustahik_tidak_aktif_ditolak(): void
    {
        $scenario = $this->scenario();
        $this->loginAs($scenario['maker'], $scenario['organization']);

        Mustahik::withoutGlobalScopes()->where('id', $scenario['mustahik'])->update(['status' => 'inactive']);

        $this->postJson('/api/v1/distributions', [
            'mustahik_id' => $scenario['mustahik'],
            'fund_id' => $scenario['fund'],
            'distribution_type' => 'cash',
            'requested_amount' => 1000000,
        ])->assertStatus(409);
    }

    // -------------------------------------------------------------- keamanan

    public function test_distribution_organisasi_lain_tidak_dapat_diakses(): void
    {
        $scenarioA = $this->scenario();
        $id = $this->untilProcessing($scenarioA);

        $organizationB = $this->organization();
        $adminB = $this->member($organizationB, 'ADMIN', ['email' => 'lain@example.test']);

        $this->loginAs($adminB, $organizationB);
        $this->getJson("/api/v1/distributions/{$id}")->assertNotFound();
        $this->postJson("/api/v1/distributions/{$id}/complete", ['amount' => 1000])->assertNotFound();
        $this->getJson('/api/v1/distributions')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_permission_kurang_ditolak(): void
    {
        $scenario = $this->scenario();
        $id = $this->untilProcessing($scenario);

        $viewer = $this->member($scenario['organization'], 'VIEWER', ['email' => 'viewer@example.test']);
        $this->loginAs($viewer, $scenario['organization']);

        $this->postJson("/api/v1/distributions/{$id}/complete", ['amount' => 1000])->assertForbidden();
    }

    public function test_mustahik_dari_organisasi_lain_tidak_lolos_validasi(): void
    {
        $scenarioA = $this->scenario();
        $organizationB = $this->organization();
        $adminB = $this->member($organizationB, 'ADMIN', ['email' => 'adminb@example.test']);

        $this->loginAs($adminB, $organizationB);
        $fundB = $this->postJson('/api/v1/funds', ['fund_code' => 'FUNDB1', 'name' => 'Fund B', 'fund_type' => 'zakat', 'opening_balance' => 5000000])->assertCreated()->json('data.id');

        $this->postJson('/api/v1/distributions', [
            'mustahik_id' => $scenarioA['mustahik'],
            'fund_id' => $fundB,
            'distribution_type' => 'cash',
            'requested_amount' => 1000000,
        ])->assertStatus(422);
    }
}
