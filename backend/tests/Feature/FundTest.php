<?php

namespace Tests\Feature;

use App\Enums\CalculationMethod;
use App\Enums\MuzakiStatus;
use App\Enums\MuzakiType;
use App\Enums\ZakatStatus;
use App\Models\Fund;
use App\Models\FundMovement;
use App\Models\Muzaki;
use App\Models\Organization;
use App\Models\User;
use App\Models\ZakatCategory;
use App\Models\ZakatType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FundTest extends TestCase
{
    use RefreshDatabase;

    public function test_fund_movement_reservation_dan_negative_balance_dicegah(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization);
        $this->loginAs($admin, $organization);

        $fund = $this->postJson('/api/v1/funds', ['fund_code' => 'ZAKATGENERAL2026', 'name' => 'Zakat General', 'fund_type' => 'zakat', 'opening_balance' => 10000000])->assertCreated()->json('data.id');
        $this->postJson("/api/v1/funds/{$fund}/inflow", ['amount' => 5000000, 'reason' => 'Collection completed'])->assertCreated();
        $this->postJson("/api/v1/funds/{$fund}/reservations", ['amount' => 3000000, 'target_type' => 'program', 'reason' => 'Program bantuan'])->assertCreated();
        $this->postJson("/api/v1/funds/{$fund}/check-availability", ['amount' => 13000000, 'reason' => 'Availability check'])->assertOk()->assertJsonPath('data.available', false);
        $this->postJson("/api/v1/funds/{$fund}/outflow", ['amount' => 13000000, 'reason' => 'Distribution'])->assertStatus(409);
        $this->assertDatabaseHas('fund_movements', ['fund_id' => $fund, 'movement_type' => 'collection_inflow']);
        $this->assertDatabaseHas('fund_reservations', ['fund_id' => $fund, 'status' => 'active']);
        $this->assertDatabaseHas('funds', ['id' => $fund, 'available_balance' => '12000000.00']);
    }

    /** F-01 — retry atau klik ganda tidak boleh menambah saldo. */
    public function test_inflow_dari_collection_bersifat_idempoten(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization);
        $collection = $this->completedCollection($organization, $admin, '1000000');

        $fund = $this->postJson('/api/v1/funds', ['fund_code' => 'IDEMPOTEN01', 'name' => 'Fund Idempoten', 'fund_type' => 'zakat', 'opening_balance' => 0])->assertCreated()->json('data.id');

        foreach (range(1, 3) as $ignored) {
            $this->postJson('/api/v1/funds/inflow-from-collection', ['fund_id' => $fund, 'collection_id' => $collection])->assertSuccessful();
        }

        $this->assertDatabaseHas('funds', ['id' => $fund, 'current_balance' => '1000000.00']);
        $this->assertSame(1, FundMovement::withoutGlobalScopes()->where('source_id', $collection)->count());
    }

    /** F-04 — pengecekan saldo wajib mengunci baris fund. */
    public function test_pengecekan_saldo_mengunci_baris_fund(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization);
        $this->loginAs($admin, $organization);

        $fund = $this->postJson('/api/v1/funds', ['fund_code' => 'LOCKUJI01', 'name' => 'Fund Lock', 'fund_type' => 'zakat', 'opening_balance' => 5000000])->assertCreated()->json('data.id');

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = strtolower($query->sql);
        });

        $this->postJson("/api/v1/funds/{$fund}/outflow", ['amount' => 1000000, 'reason' => 'Uji lock'])->assertCreated();

        $locking = array_filter($queries, fn (string $sql) => str_contains($sql, 'from "funds"') && str_contains($sql, 'for update'));

        $this->assertNotEmpty($locking, 'Baris fund harus dikunci sebelum saldo dicek dan movement ditulis.');
    }

    /** F-05 — over-reservation tampak apa adanya, tidak dibulatkan ke nol. */
    public function test_available_balance_negatif_tidak_disembunyikan(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization);
        $this->loginAs($admin, $organization);

        $fund = $this->postJson('/api/v1/funds', ['fund_code' => 'NEGATIF01', 'name' => 'Fund Negatif', 'fund_type' => 'zakat', 'opening_balance' => 1000000])->assertCreated()->json('data.id');
        $this->postJson("/api/v1/funds/{$fund}/reservations", ['amount' => 1000000, 'target_type' => 'program', 'reason' => 'Tahan penuh'])->assertCreated();

        // Saldo dikurangi lewat penyesuaian sehingga reservation melebihi dana riil.
        $koreksi = new FundMovement;
        $koreksi->forceFill([
            'movement_number' => 'FND2026999999',
            'organization_id' => $organization->id,
            'fund_id' => $fund,
            'movement_type' => 'adjustment',
            'direction' => 'out',
            'amount' => '400000',
            'currency' => 'IDR',
            'source_type' => 'fund_adjustment',
            'description' => 'Koreksi manual',
            'effective_at' => now(),
        ])->save();

        $this->getJson("/api/v1/funds/{$fund}/balance")->assertOk();

        $this->assertSame('-400000.00', (string) Fund::withoutGlobalScopes()->find($fund)->available_balance);
    }

    private function completedCollection(Organization $organization, User $admin, string $amount): string
    {
        $muzaki = new Muzaki;
        $muzaki->fill(['display_name' => 'Muzaki Fund', 'registration_source' => 'manual', 'registered_at' => now()]);
        $muzaki->forceFill(['organization_id' => $organization->id, 'muzaki_type' => MuzakiType::Individual, 'status' => MuzakiStatus::Active])->save();
        $category = new ZakatCategory;
        $category->fill(['code' => 'ZK_FUND', 'name' => 'Zakat Fund'])->forceFill(['organization_id' => $organization->id])->save();
        $type = new ZakatType;
        $type->fill(['zakat_category_id' => $category->id, 'code' => 'ZK_FUND', 'name' => 'Zakat Fund', 'calculation_method' => CalculationMethod::Fixed, 'status' => ZakatStatus::Active])->forceFill(['organization_id' => $organization->id])->save();

        $this->loginAs($admin, $organization);
        $id = $this->postJson('/api/v1/collections', ['muzaki_id' => $muzaki->id, 'zakat_type_id' => $type->id, 'expected_amount' => $amount, 'source' => 'manual', 'reason' => 'Uji fund'])->assertCreated()->json('data.id');
        $this->postJson("/api/v1/collections/{$id}/confirm")->assertOk();
        $payment = $this->postJson("/api/v1/collections/{$id}/payments", ['payment_reference' => 'PAY-FUND', 'amount' => $amount, 'payment_method' => 'CASH'])->assertCreated()->json('data.id');
        $this->postJson("/api/v1/collections/payments/{$payment}/verify", ['status' => 'settled'])->assertOk();

        return $id;
    }
}
