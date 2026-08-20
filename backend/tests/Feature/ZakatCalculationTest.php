<?php

namespace Tests\Feature;

use App\Enums\CalculationMethod;
use App\Enums\MuzakiStatus;
use App\Enums\MuzakiType;
use App\Enums\ZakatStatus;
use App\Models\Muzaki;
use App\Models\ZakatCategory;
use App\Models\ZakatNisab;
use App\Models\ZakatRate;
use App\Models\ZakatRule;
use App\Models\ZakatType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZakatCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculation_menyimpan_snapshot_dan_breakdown(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization);
        $muzaki = new Muzaki;
        $muzaki->fill(['display_name' => 'Muzaki Test', 'registration_source' => 'manual', 'registered_at' => now()]);
        $muzaki->organization_id = $organization->id;
        $muzaki->muzaki_type = MuzakiType::Individual;
        $muzaki->status = MuzakiStatus::Active;
        $muzaki->save();
        $category = new ZakatCategory;
        $category->fill(['code' => 'ZAKAT_MAL', 'name' => 'Zakat Mal'])->forceFill(['organization_id' => $organization->id])->save();
        $type = new ZakatType;
        $type->fill(['zakat_category_id' => $category->id, 'code' => 'ZAKAT_EMAS', 'name' => 'Zakat Emas', 'calculation_method' => CalculationMethod::NisabBased, 'status' => ZakatStatus::Active])->forceFill(['organization_id' => $organization->id])->save();
        $rule = new ZakatRule;
        $rule->fill(['zakat_type_id' => $type->id, 'rule_code' => 'ZAKAT_EMAS2026V1', 'name' => 'Rule Emas 2026', 'version' => 1, 'effective_from' => '2026-01-01'])->forceFill(['organization_id' => $organization->id, 'status' => ZakatStatus::Active])->save();
        ZakatRate::create(['zakat_rule_id' => $rule->id, 'rate_type' => 'percentage', 'rate_value' => 2.5, 'unit' => 'percent', 'effective_from' => '2026-01-01']);
        ZakatNisab::create(['zakat_rule_id' => $rule->id, 'nisab_type' => 'fixed', 'reference_value' => 85000000, 'effective_from' => '2026-01-01']);

        $response = $this->loginAs($admin, $organization)->postJson('/api/v1/zakat/calculations', [
            'muzaki_id' => $muzaki->id,
            'zakat_type_id' => $type->id,
            'calculation_date' => '2026-08-20',
            'inputs' => ['TOTAL_ASSET' => 100000000, 'DEDUCTION' => 10000000],
        ])->assertCreated();

        $id = $response->json('data.id');
        $this->postJson("/api/v1/zakat/calculations/{$id}/calculate")->assertOk()->assertJsonPath('data.eligibility_status', 'eligible')->assertJsonPath('data.zakat_amount', '2250000.00000000');
        $this->assertDatabaseHas('zakat_calculation_snapshots', ['calculation_id' => $id]);
    }
}
