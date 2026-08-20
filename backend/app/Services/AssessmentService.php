<?php

namespace App\Services;

use App\Exceptions\ZakatException;
use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentRequest;
use App\Models\AssessmentReview;
use App\Models\AssessmentTemplate;
use App\Models\Mustahik;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AssessmentService
{
    public function __construct(private readonly AuditService $audit) {}

    public function requestList(array $filters): LengthAwarePaginator
    {
        return AssessmentRequest::with('mustahik:id,mustahik_number,display_name')
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['priority'] ?? null, fn ($q, $v) => $q->where('priority', $v))
            ->latest()->paginate($this->perPage($filters));
    }

    public function createRequest(array $data): AssessmentRequest
    {
        $mustahik = Mustahik::find($data['mustahik_id']) ?? throw ZakatException::notFound('Mustahik tidak ditemukan.');
        $request = AssessmentRequest::create([
            'request_number' => app(BusinessNumberService::class)->next('ASR'),
            'mustahik_id' => $mustahik->id,
            'assessment_type' => $data['assessment_type'],
            'priority' => $data['priority'] ?? 'normal',
            'reason' => $data['reason'] ?? null,
            'requested_by' => auth()->id(),
            'requested_at' => now(),
            'due_date' => $data['due_date'] ?? null,
            'status' => 'draft',
            'notes' => $data['notes'] ?? null,
        ]);
        $this->audit->record('assessment_request_created', $request);

        return $this->request($request->id);
    }

    public function request(string $id): AssessmentRequest
    {
        return AssessmentRequest::with(['mustahik', 'assessments'])->find($id) ?? throw ZakatException::notFound('Assessment request tidak ditemukan.');
    }

    public function assign(AssessmentRequest $request, array $data): AssessmentRequest
    {
        if (in_array($request->status, ['cancelled', 'completed'], true)) {
            throw ZakatException::invalidTransition('Assessment request tidak dapat ditugaskan pada status ini.');
        }
        $request->forceFill(['assessor_id' => $data['assessor_id'], 'assigned_at' => now(), 'due_date' => $data['due_date'] ?? $request->due_date, 'status' => 'assigned'])->saveQuietly();
        $this->audit->record('assessment_request_assigned', $request);

        return $this->request($request->id);
    }

    public function cancelRequest(AssessmentRequest $request, string $reason): AssessmentRequest
    {
        if (in_array($request->status, ['completed', 'cancelled'], true)) {
            throw ZakatException::invalidTransition('Assessment request sudah selesai.');
        }
        $request->forceFill(['status' => 'cancelled', 'notes' => $reason])->saveQuietly();
        $this->audit->record('assessment_request_cancelled', $request, context: ['reason' => $reason]);

        return $this->request($request->id);
    }

    public function templateList(array $filters): LengthAwarePaginator
    {
        return AssessmentTemplate::query()->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->latest()->paginate($this->perPage($filters));
    }

    public function createTemplate(array $data): AssessmentTemplate
    {
        $template = AssessmentTemplate::create($data + ['version' => 1, 'status' => 'draft']);
        $this->audit->record('assessment_template_created', $template);

        return $template;
    }

    public function publishTemplate(AssessmentTemplate $template): AssessmentTemplate
    {
        $template->forceFill(['status' => 'published', 'effective_from' => $template->effective_from ?? today()])->saveQuietly();
        $this->audit->record('assessment_template_published', $template);

        return $template;
    }

    public function list(array $filters): LengthAwarePaginator
    {
        return Assessment::with(['mustahik:id,mustahik_number,display_name', 'request:id,request_number', 'template:id,name,version'])->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->when($filters['mustahik_id'] ?? null, fn ($q, $v) => $q->where('mustahik_id', $v))->latest()->paginate($this->perPage($filters));
    }

    public function create(array $data): Assessment
    {
        $request = $this->request($data['assessment_request_id']);
        if (in_array($request->status, ['cancelled', 'completed'], true)) {
            throw ZakatException::invalidTransition('Assessment request tidak aktif.');
        }
        $template = ! empty($data['template_id']) ? AssessmentTemplate::find($data['template_id']) : null;

        return DB::transaction(function () use ($data, $request, $template) {
            $assessment = Assessment::create([
                'assessment_number' => app(BusinessNumberService::class)->next('ASM'),
                'assessment_request_id' => $request->id,
                'mustahik_id' => $request->mustahik_id,
                'template_id' => $template?->id,
                'template_version' => $template?->version,
                'assessment_type' => $request->assessment_type,
                'assessor_id' => $data['assessor_id'] ?? $request->assessor_id,
                'assessment_date' => $data['assessment_date'] ?? today(),
                'started_at' => now(),
                'status' => 'in_progress',
            ]);
            $request->forceFill(['status' => 'in_progress'])->saveQuietly();
            $this->audit->record('assessment_started', $assessment);

            return $this->find($assessment->id);
        });
    }

    public function find(string $id): Assessment
    {
        return Assessment::with(['mustahik', 'request', 'template', 'answers', 'reviews'])->find($id) ?? throw ZakatException::notFound('Assessment tidak ditemukan.');
    }

    public function update(Assessment $assessment, array $data): Assessment
    {
        $this->assertEditable($assessment);
        $answers = $data['answers'] ?? [];
        $assessment->fill(collect($data)->except('answers')->all())->save();
        foreach ($answers as $answer) {
            AssessmentAnswer::updateOrCreate(
                ['assessment_id' => $assessment->id, 'question_code' => $answer['question_code']],
                ['question_id' => $answer['question_id'] ?? null, 'answer_value' => $answer['answer_value'] ?? null, 'answer_data' => $answer['answer_data'] ?? null, 'score' => $answer['score'] ?? null, 'notes' => $answer['notes'] ?? null, 'question_snapshot' => $answer['question_snapshot'] ?? null]
            );
        }
        $assessment->forceFill(['total_score' => $assessment->answers()->sum('score')])->saveQuietly();
        $this->audit->record('assessment_updated', $assessment);

        return $this->find($assessment->id);
    }

    public function submit(Assessment $assessment): Assessment
    {
        $this->assertEditable($assessment);
        $assessment->forceFill(['status' => 'submitted', 'submitted_at' => now(), 'total_score' => $assessment->answers()->sum('score')])->saveQuietly();
        $this->audit->record('assessment_submitted', $assessment);

        return $this->find($assessment->id);
    }

    public function review(Assessment $assessment, array $data): Assessment
    {
        if (! in_array($assessment->status, ['submitted', 'under_review'], true)) {
            throw ZakatException::invalidTransition('Assessment belum siap direview.');
        }
        $decision = $data['decision'];
        $status = ['approve' => 'approved', 'return' => 'returned', 'reject' => 'rejected', 'escalate' => 'under_review'][$decision];
        AssessmentReview::create(['assessment_id' => $assessment->id, 'reviewer_id' => auth()->id(), 'decision' => $decision, 'notes' => $data['notes'] ?? null, 'reviewed_at' => now()]);
        $assessment->forceFill(['status' => $status, 'approved_at' => $status === 'approved' ? now() : null, 'review_notes' => $data['notes'] ?? null])->saveQuietly();
        $this->audit->record($status === 'approved' ? 'assessment_approved' : 'assessment_reviewed', $assessment, context: ['decision' => $decision]);

        return $this->find($assessment->id);
    }

    public function reassess(Assessment $previous, array $data): Assessment
    {
        $request = $this->createRequest(['mustahik_id' => $previous->mustahik_id, 'assessment_type' => 'reassessment', 'priority' => $data['priority'] ?? 'normal', 'reason' => $data['reason'] ?? 'Reassessment']);
        $assessment = $this->create(['assessment_request_id' => $request->id, 'assessor_id' => $data['assessor_id'] ?? null]);
        $assessment->forceFill(['previous_assessment_id' => $previous->id])->saveQuietly();
        $this->audit->record('assessment_reassessed', $assessment, context: ['previous_assessment_id' => $previous->id]);

        return $this->find($assessment->id);
    }

    private function assertEditable(Assessment $assessment): void
    {
        if (! in_array($assessment->status, ['draft', 'assigned', 'in_progress', 'returned'], true)) {
            throw ZakatException::invalidTransition('Assessment sudah immutable pada status ini.');
        }
    }

    private function perPage(array $filters): int
    {
        return min((int) ($filters['per_page'] ?? 15), (int) config('zakat.pagination.max_per_page'));
    }
}
