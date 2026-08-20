<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramDistributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_program_enrollment_dan_distribution_mengikuti_approval_reservation_dan_fund_movement(): void
    {
        $organization = $this->organization();
        $maker = $this->member($organization);
        $checker = $this->member($organization, 'ADMIN', ['email' => 'checker@example.test']);
        $this->loginAs($maker, $organization);
        $mustahik = $this->postJson('/api/v1/mustahiks', ['full_name' => 'Penerima Program'])->assertCreated()->json('data.id');
        $fund = $this->postJson('/api/v1/funds', ['fund_code' => 'PROGRAM2026', 'name' => 'Fund Program', 'fund_type' => 'zakat', 'opening_balance' => 10000000])->assertCreated()->json('data.id');
        $program = $this->postJson('/api/v1/programs', ['name' => 'Bantuan Usaha', 'program_type' => 'empowerment', 'capacity_limit' => 5])->assertCreated()->json('data.id');
        $this->postJson("/api/v1/programs/{$program}/submit")->assertOk();
        $this->loginAs($checker, $organization);
        $this->postJson("/api/v1/programs/{$program}/approve")->assertOk()->assertJsonPath('data.status', 'active');
        $enrollment = $this->postJson("/api/v1/programs/{$program}/enrollments", ['mustahik_id' => $mustahik, 'eligibility_result' => 'eligible'])->assertCreated()->json('data.id');
        $this->postJson("/api/v1/program-enrollments/{$enrollment}/approve")->assertOk()->assertJsonPath('data.status', 'approved');
        $distribution = $this->postJson('/api/v1/distributions', ['mustahik_id' => $mustahik, 'fund_id' => $fund, 'program_id' => $program, 'program_enrollment_id' => $enrollment, 'distribution_type' => 'cash', 'source_type' => 'program', 'requested_amount' => 2500000, 'description' => 'Modal usaha'])->assertCreated()->json('data.id');
        $this->postJson("/api/v1/distributions/{$distribution}/submit")->assertOk();
        $this->postJson("/api/v1/distributions/{$distribution}/approve")->assertForbidden();
        $this->loginAs($maker, $organization);
        $this->postJson("/api/v1/distributions/{$distribution}/approve")->assertOk()->assertJsonPath('data.status', 'approved');
        $this->postJson("/api/v1/distributions/{$distribution}/reserve")->assertOk()->assertJsonPath('data.status', 'reserved');
        $this->postJson("/api/v1/distributions/{$distribution}/process")->assertOk();
        $this->postJson("/api/v1/distributions/{$distribution}/complete", ['amount' => 2500000])->assertOk()->assertJsonPath('data.status', 'completed');
        $this->assertDatabaseHas('fund_movements', ['fund_id' => $fund, 'movement_type' => 'distribution', 'direction' => 'out']);
        $this->assertDatabaseHas('distributions', ['id' => $distribution, 'distributed_amount' => '2500000.00', 'status' => 'completed']);
    }
}
