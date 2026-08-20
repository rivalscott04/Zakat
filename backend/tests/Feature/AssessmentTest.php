<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_assessment_request_workflow_submit_review_dan_reassessment(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization);
        $this->loginAs($admin, $organization);
        $mustahik = $this->postJson('/api/v1/mustahiks', ['full_name' => 'Budi Assessment'])->assertCreated()->json('data.id');
        $request = $this->postJson('/api/v1/assessment-requests', ['mustahik_id' => $mustahik, 'assessment_type' => 'initial', 'priority' => 'high', 'reason' => 'Verifikasi kelayakan'])->assertCreated()->assertJsonPath('data.request_number', fn ($value) => str_starts_with($value, 'ASR'))->json('data.id');
        $assessment = $this->postJson('/api/v1/assessments', ['assessment_request_id' => $request])->assertCreated()->assertJsonPath('data.status', 'in_progress')->json('data.id');
        $this->patchJson("/api/v1/assessments/{$assessment}", ['recommendation' => 'needs_review', 'answers' => [['question_code' => 'MONTHLYINCOME', 'answer_value' => '1000000', 'score' => 8]]])->assertOk()->assertJsonPath('data.total_score', '8.00');
        $this->postJson("/api/v1/assessments/{$assessment}/submit")->assertOk()->assertJsonPath('data.status', 'submitted');
        $this->postJson("/api/v1/assessments/{$assessment}/review", ['decision' => 'approve', 'notes' => 'Disetujui'])->assertOk()->assertJsonPath('data.status', 'approved');
        $this->postJson("/api/v1/assessments/{$assessment}/reassess", ['reason' => 'Review berkala'])->assertCreated()->assertJsonPath('data.previous_assessment_id', $assessment);
        $this->assertDatabaseHas('assessment_answers', ['assessment_id' => $assessment, 'question_code' => 'MONTHLYINCOME']);
    }
}
