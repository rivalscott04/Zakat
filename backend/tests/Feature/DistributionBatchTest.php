<?php

namespace Tests\Feature;

use App\Models\Distribution;
use App\Models\Fund;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PRD 12P §41 — alur batch distribution. */
class DistributionBatchTest extends TestCase
{
    use RefreshDatabase;

    private function scenario(string $openingBalance = '10000000'): array
    {
        $organization = $this->organization();
        $maker = $this->member($organization);
        $checker = $this->member($organization, 'ADMIN', ['email' => 'checker'.uniqid().'@example.test']);

        $this->loginAs($maker, $organization);

        $mustahiks = collect(range(1, 3))
            ->map(fn ($i) => $this->postJson('/api/v1/mustahiks', ['full_name' => "Penerima Batch {$i}"])->assertCreated()->json('data.id'))
            ->all();

        $fund = $this->postJson('/api/v1/funds', [
            'fund_code' => 'BATCH'.strtoupper(substr(uniqid(), -5)),
            'name' => 'Fund Batch',
            'fund_type' => 'zakat',
            'opening_balance' => $openingBalance,
        ])->assertCreated()->json('data.id');

        return compact('organization', 'maker', 'checker', 'mustahiks', 'fund');
    }

    private function batchWithBeneficiaries(array $scenario, string $amount = '1000000'): string
    {
        $batch = $this->postJson('/api/v1/distribution-batches', [
            'name' => 'Beasiswa Semester Ganjil',
            'fund_id' => $scenario['fund'],
            'distribution_type' => 'cash',
        ])->assertCreated()->json('data.id');

        foreach ($scenario['mustahiks'] as $mustahik) {
            $this->postJson("/api/v1/distribution-batches/{$batch}/beneficiaries", [
                'mustahik_id' => $mustahik,
                'approved_amount' => $amount,
            ])->assertCreated();
        }

        return $batch;
    }

    public function test_alur_batch_penuh_menyalurkan_ke_seluruh_beneficiary(): void
    {
        $scenario = $this->scenario();
        $batch = $this->batchWithBeneficiaries($scenario);

        $this->getJson("/api/v1/distribution-batches/{$batch}")
            ->assertOk()
            ->assertJsonPath('data.total_beneficiary', 3)
            ->assertJsonPath('data.total_amount', '3000000.00');

        $this->postJson("/api/v1/distribution-batches/{$batch}/validate")->assertOk()->assertJsonPath('data.status', 'validated');
        $this->postJson("/api/v1/distribution-batches/{$batch}/submit")->assertOk()->assertJsonPath('data.status', 'pending_approval');

        $this->loginAs($scenario['checker'], $scenario['organization']);
        $this->postJson("/api/v1/distribution-batches/{$batch}/approve")->assertOk()->assertJsonPath('data.status', 'approved');

        // Dana seluruh batch ditahan sejak approval (PRD 12P §41).
        $fund = Fund::withoutGlobalScopes()->find($scenario['fund']);
        $this->assertSame('3000000.00', (string) $fund->reserved_balance);

        $this->postJson("/api/v1/distribution-batches/{$batch}/process")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $fund = Fund::withoutGlobalScopes()->find($scenario['fund']);
        $this->assertSame('7000000.00', (string) $fund->current_balance);
        $this->assertSame('0.00', (string) $fund->reserved_balance);

        // Satu distribution per penerima, seluruhnya completed dan menerbitkan event.
        $this->assertDatabaseCount('distributions', 3);
        $this->assertSame(3, Distribution::withoutGlobalScopes()->where('status', 'completed')->count());
        $this->assertDatabaseCount('accounting_events', 3);
    }

    public function test_maker_tidak_dapat_menyetujui_batch_sendiri(): void
    {
        $scenario = $this->scenario();
        $batch = $this->batchWithBeneficiaries($scenario);

        $this->postJson("/api/v1/distribution-batches/{$batch}/validate")->assertOk();
        $this->postJson("/api/v1/distribution-batches/{$batch}/submit")->assertOk();
        $this->postJson("/api/v1/distribution-batches/{$batch}/approve")->assertForbidden();
    }

    public function test_batch_tanpa_beneficiary_tidak_dapat_divalidasi(): void
    {
        $scenario = $this->scenario();

        $batch = $this->postJson('/api/v1/distribution-batches', [
            'name' => 'Batch Kosong',
            'fund_id' => $scenario['fund'],
            'distribution_type' => 'cash',
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/distribution-batches/{$batch}/validate")->assertStatus(409);
    }

    public function test_saldo_tidak_cukup_untuk_total_batch_ditolak(): void
    {
        $scenario = $this->scenario('2000000');
        $batch = $this->batchWithBeneficiaries($scenario);

        // Tiap beneficiary lolos sendiri-sendiri, tetapi totalnya melebihi saldo.
        $this->postJson("/api/v1/distribution-batches/{$batch}/validate")
            ->assertStatus(409)
            ->assertJsonPath('code', 'CONFLICT');
    }

    public function test_mustahik_ganda_dalam_satu_batch_ditolak(): void
    {
        $scenario = $this->scenario();
        $batch = $this->batchWithBeneficiaries($scenario);

        $this->postJson("/api/v1/distribution-batches/{$batch}/beneficiaries", [
            'mustahik_id' => $scenario['mustahiks'][0],
            'approved_amount' => 500000,
        ])->assertStatus(409)->assertJsonPath('code', 'DUPLICATE_RESOURCE');
    }

    public function test_beneficiary_tidak_dapat_diubah_setelah_batch_disetujui(): void
    {
        $scenario = $this->scenario();
        $batch = $this->batchWithBeneficiaries($scenario);

        $this->postJson("/api/v1/distribution-batches/{$batch}/validate")->assertOk();
        $this->postJson("/api/v1/distribution-batches/{$batch}/submit")->assertOk();

        $this->loginAs($scenario['checker'], $scenario['organization']);
        $this->postJson("/api/v1/distribution-batches/{$batch}/approve")->assertOk();

        $mustahik = $this->postJson('/api/v1/mustahiks', ['full_name' => 'Penerima Susulan'])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/distribution-batches/{$batch}/beneficiaries", [
            'mustahik_id' => $mustahik,
            'approved_amount' => 100000,
        ])->assertStatus(409);
    }

    public function test_pembatalan_batch_melepas_reservation(): void
    {
        $scenario = $this->scenario();
        $batch = $this->batchWithBeneficiaries($scenario);

        $this->postJson("/api/v1/distribution-batches/{$batch}/validate")->assertOk();
        $this->postJson("/api/v1/distribution-batches/{$batch}/submit")->assertOk();

        $this->loginAs($scenario['checker'], $scenario['organization']);
        $this->postJson("/api/v1/distribution-batches/{$batch}/approve")->assertOk();
        $this->postJson("/api/v1/distribution-batches/{$batch}/cancel", ['reason' => 'Program ditunda'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $fund = Fund::withoutGlobalScopes()->find($scenario['fund']);
        $this->assertSame('0.00', (string) $fund->reserved_balance);
        $this->assertSame('10000000.00', (string) $fund->current_balance);
    }

    public function test_batch_organisasi_lain_tidak_terlihat(): void
    {
        $scenario = $this->scenario();
        $batch = $this->batchWithBeneficiaries($scenario);

        $organizationB = $this->organization();
        $adminB = $this->member($organizationB, 'ADMIN', ['email' => 'lainbatch@example.test']);

        $this->loginAs($adminB, $organizationB);
        $this->getJson("/api/v1/distribution-batches/{$batch}")->assertNotFound();
        $this->getJson('/api/v1/distribution-batches')->assertOk()->assertJsonCount(0, 'data');
    }
}
