<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
