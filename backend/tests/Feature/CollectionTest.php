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
        $this->postJson("/api/v1/collections/payments/{$payment}/verify", ['status' => 'settled'])->assertOk()->assertJsonPath('data.status', 'partially_paid')->assertJsonPath('data.remaining_amount', '7000000.00000000');
        $payment = $this->postJson("/api/v1/collections/{$id}/payments", ['payment_reference' => 'PAY-002', 'amount' => 7000000, 'payment_method' => 'BANK_TRANSFER'])->assertCreated()->json('data.id');
        $this->postJson("/api/v1/collections/payments/{$payment}/verify", ['status' => 'settled'])->assertOk()->assertJsonPath('data.status', 'completed')->assertJsonPath('data.remaining_amount', '0.00000000');
        $this->assertDatabaseHas('payment_allocations', ['collection_id' => $id]);
    }
}
