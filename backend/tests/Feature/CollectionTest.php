<?php

namespace Tests\Feature;

use App\Enums\CalculationMethod;
use App\Enums\MuzakiStatus;
use App\Enums\MuzakiType;
use App\Enums\ZakatStatus;
use App\Models\Muzaki;
use App\Models\ZakatCategory;
use App\Models\ZakatType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_collection_mendukung_partial_payment_dan_settlement(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization);
        $muzaki = new Muzaki;
        $muzaki->fill(['display_name' => 'Muzaki Collection', 'registration_source' => 'manual', 'registered_at' => now()]);
        $muzaki->forceFill(['organization_id' => $organization->id, 'muzaki_type' => MuzakiType::Individual, 'status' => MuzakiStatus::Active])->save();
        $category = new ZakatCategory;
        $category->fill(['code' => 'ZAKAT_COL', 'name' => 'Zakat Collection'])->forceFill(['organization_id' => $organization->id])->save();
        $type = new ZakatType;
        $type->fill(['zakat_category_id' => $category->id, 'code' => 'ZAKAT_COL', 'name' => 'Zakat Collection', 'calculation_method' => CalculationMethod::Fixed, 'status' => ZakatStatus::Active])->forceFill(['organization_id' => $organization->id])->save();

        $id = $this->loginAs($admin, $organization)->postJson('/api/v1/collections', ['muzaki_id' => $muzaki->id, 'zakat_type_id' => $type->id, 'expected_amount' => 10000000, 'source' => 'manual', 'reason' => 'Kewajiban manual'])->assertCreated()->assertJsonPath('data.status', 'draft')->json('data.id');
        $this->postJson("/api/v1/collections/{$id}/confirm")->assertOk()->assertJsonPath('data.status', 'pending');
        $payment = $this->postJson("/api/v1/collections/{$id}/payments", ['payment_reference' => 'PAY-001', 'amount' => 3000000, 'payment_method' => 'CASH'])->assertCreated()->json('data.id');
        $this->postJson("/api/v1/collections/payments/{$payment}/verify", ['status' => 'settled'])->assertOk()->assertJsonPath('data.status', 'partially_paid')->assertJsonPath('data.remaining_amount', '7000000.00');
        $payment = $this->postJson("/api/v1/collections/{$id}/payments", ['payment_reference' => 'PAY-002', 'amount' => 7000000, 'payment_method' => 'BANK_TRANSFER'])->assertCreated()->json('data.id');
        $this->postJson("/api/v1/collections/payments/{$payment}/verify", ['status' => 'settled'])->assertOk()->assertJsonPath('data.status', 'completed')->assertJsonPath('data.remaining_amount', '0.00');
        $this->assertDatabaseHas('payment_allocations', ['collection_id' => $id]);
    }

    /** F-09 — endpoint ringkasan sempat selalu 500 karena whereColumn dengan literal. */
    public function test_summary_collection_dapat_diakses(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization);
        $muzaki = new Muzaki;
        $muzaki->fill(['display_name' => 'Muzaki Summary', 'registration_source' => 'manual', 'registered_at' => now()]);
        $muzaki->forceFill(['organization_id' => $organization->id, 'muzaki_type' => MuzakiType::Individual, 'status' => MuzakiStatus::Active])->save();
        $category = new ZakatCategory;
        $category->fill(['code' => 'ZAKAT_SUM', 'name' => 'Zakat Summary'])->forceFill(['organization_id' => $organization->id])->save();
        $type = new ZakatType;
        $type->fill(['zakat_category_id' => $category->id, 'code' => 'ZAKAT_SUM', 'name' => 'Zakat Summary', 'calculation_method' => CalculationMethod::Fixed, 'status' => ZakatStatus::Active])->forceFill(['organization_id' => $organization->id])->save();

        $this->loginAs($admin, $organization);

        // Collection lewat jatuh tempo dengan sisa tagihan: kondisi yang memicu bug lama.
        $id = $this->postJson('/api/v1/collections', ['muzaki_id' => $muzaki->id, 'zakat_type_id' => $type->id, 'expected_amount' => 500000, 'source' => 'manual', 'reason' => 'Jatuh tempo', 'due_date' => now()->subDay()->toDateString()])->assertCreated()->json('data.id');
        $this->postJson("/api/v1/collections/{$id}/confirm")->assertOk();

        $this->getJson('/api/v1/collections/summary')
            ->assertOk()
            ->assertJsonStructure(['data' => ['total_collections', 'total_expected', 'total_paid', 'total_remaining']]);
    }

    /** F-06 dan F-07 — uang collection berskala dua desimal dan dihitung tanpa float. */
    public function test_pembayaran_lebih_terdeteksi_dan_nominal_berskala_dua_desimal(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization);
        $muzaki = new Muzaki;
        $muzaki->fill(['display_name' => 'Muzaki Presisi', 'registration_source' => 'manual', 'registered_at' => now()]);
        $muzaki->forceFill(['organization_id' => $organization->id, 'muzaki_type' => MuzakiType::Individual, 'status' => MuzakiStatus::Active])->save();
        $category = new ZakatCategory;
        $category->fill(['code' => 'ZK_PRES', 'name' => 'Zakat Presisi'])->forceFill(['organization_id' => $organization->id])->save();
        $type = new ZakatType;
        $type->fill(['zakat_category_id' => $category->id, 'code' => 'ZK_PRES', 'name' => 'Zakat Presisi', 'calculation_method' => CalculationMethod::Fixed, 'status' => ZakatStatus::Active])->forceFill(['organization_id' => $organization->id])->save();

        $this->loginAs($admin, $organization);
        $id = $this->postJson('/api/v1/collections', ['muzaki_id' => $muzaki->id, 'zakat_type_id' => $type->id, 'expected_amount' => 1000000, 'source' => 'manual', 'reason' => 'Uji presisi'])->assertCreated()->json('data.id');
        $this->postJson("/api/v1/collections/{$id}/confirm")->assertOk();

        // Bayar lebih besar dari tagihan: kelebihannya tidak boleh ikut teralokasi.
        $payment = $this->postJson("/api/v1/collections/{$id}/payments", ['payment_reference' => 'PAY-LEBIH', 'amount' => 1500000, 'payment_method' => 'CASH'])->assertCreated()->json('data.id');
        $this->postJson("/api/v1/collections/payments/{$payment}/verify", ['status' => 'settled'])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.paid_amount', '1000000.00')
            ->assertJsonPath('data.remaining_amount', '0.00');

        $this->assertDatabaseHas('collections', ['id' => $id, 'overpayment_status' => 'detected']);
        $this->assertDatabaseHas('payment_allocations', ['collection_id' => $id, 'allocated_amount' => '1000000.00']);
    }
}
