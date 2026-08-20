<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramManagementCompleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_seluruh_konfigurasi_program_eligibility_waitlist_activity_metric_dan_dashboard(): void
    {
        $organization = $this->organization();
        $maker = $this->member($organization);
        $checker = $this->member($organization, 'ADMIN', ['email' => 'program-checker@example.test']);
        $this->loginAs($maker, $organization);
        $fund = $this->postJson('/api/v1/funds', ['fund_code' => 'PRGTEST', 'name' => 'Program Fund', 'fund_type' => 'zakat', 'opening_balance' => 10000000])->assertCreated()->json('data.id');
        $mustahikOne = $this->postJson('/api/v1/mustahiks', ['full_name' => 'Program One'])->assertCreated()->json('data.id');
        $mustahikTwo = $this->postJson('/api/v1/mustahiks', ['full_name' => 'Program Two'])->assertCreated()->json('data.id');
        $category = $this->postJson('/api/v1/programs/categories', ['category_code' => 'ECONOMIC', 'name' => 'Economic'])->assertCreated()->json('data.id');
        $program = $this->postJson('/api/v1/programs', ['name' => 'Program Lengkap', 'program_type' => 'empowerment', 'category_id' => $category, 'capacity_limit' => 1, 'waitlist_enabled' => true])->assertCreated()->json('data.id');
        $this->postJson("/api/v1/programs/{$program}/periods", ['period_code' => '2026Q1', 'name' => 'Q1 2026', 'start_date' => '2026-01-01', 'end_date' => '2026-03-31'])->assertCreated();
        $this->postJson("/api/v1/programs/{$program}/funds", ['fund_id' => $fund, 'priority' => 1])->assertCreated();
        $this->postJson("/api/v1/programs/{$program}/budgets", ['fund_id' => $fund, 'budget_amount' => 5000000])->assertCreated();
        $this->postJson("/api/v1/programs/{$program}/eligibility-rules", ['rule_code' => 'ACTIVE_MUSTAHIK', 'rule_type' => 'mustahik', 'field' => 'status', 'operator' => 'equals', 'value' => 'active', 'required' => true])->assertCreated();
        $this->postJson("/api/v1/programs/{$program}/targets", ['target_type' => 'beneficiary', 'name' => 'Penerima', 'target_value' => 1, 'unit' => 'orang'])->assertCreated();
        $this->postJson("/api/v1/programs/{$program}/outputs", ['output_code' => 'HELPED', 'name' => 'Penerima bantuan', 'target_value' => 1, 'unit' => 'orang'])->assertCreated();
        $this->postJson("/api/v1/programs/{$program}/outcomes", ['outcome_code' => 'INCOME', 'name' => 'Pendapatan meningkat', 'target_value' => 1, 'unit' => 'indicator'])->assertCreated();
        $this->postJson("/api/v1/programs/{$program}/activities", ['activity_code' => 'TRAINING01', 'name' => 'Pelatihan', 'activity_type' => 'training'])->assertCreated();
        $this->postJson("/api/v1/programs/{$program}/submit")->assertOk();
        $this->loginAs($checker, $organization);
        $this->postJson("/api/v1/programs/{$program}/approve")->assertOk()->assertJsonPath('data.status', 'active');
        $this->postJson("/api/v1/programs/{$program}/evaluate-eligibility", ['mustahik_id' => $mustahikOne])->assertCreated()->assertJsonPath('data.result', 'eligible');
        $enrollment = $this->postJson("/api/v1/programs/{$program}/enrollments", ['mustahik_id' => $mustahikOne, 'eligibility_result' => 'eligible'])->assertCreated()->json('data.id');
        $this->postJson("/api/v1/program-enrollments/{$enrollment}/approve")->assertOk();
        $this->postJson("/api/v1/programs/{$program}/enrollments", ['mustahik_id' => $mustahikTwo, 'eligibility_result' => 'eligible'])->assertStatus(409);
        $this->assertDatabaseHas('program_waitlists', ['program_id' => $program, 'mustahik_id' => $mustahikTwo, 'status' => 'waiting']);
        $this->assertSame(1, $this->getJson('/api/v1/programs/dashboard')->assertOk()->json('data.active_programs'));
    }
}
