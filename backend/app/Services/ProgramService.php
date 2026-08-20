<?php

namespace App\Services;

use App\Exceptions\ZakatException;
use App\Models\Assessment;
use App\Models\Mustahik;
use App\Models\Program;
use App\Models\ProgramActivity;
use App\Models\ProgramActivityParticipant;
use App\Models\ProgramBudget;
use App\Models\ProgramBudgetCommitment;
use App\Models\ProgramCategory;
use App\Models\ProgramEligibilityEvaluation;
use App\Models\ProgramEligibilityRule;
use App\Models\ProgramEnrollment;
use App\Models\ProgramFund;
use App\Models\ProgramOutcome;
use App\Models\ProgramOutput;
use App\Models\ProgramPeriod;
use App\Models\ProgramTarget;
use App\Models\ProgramWaitlist;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProgramService
{
    public function __construct(private readonly AuditService $audit) {}

    public function list(array $filters): LengthAwarePaginator
    {
        return Program::with('budgets')->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->when($filters['search'] ?? null, fn ($q, $v) => $q->where('name', 'ilike', "%{$v}%"))->latest()->paginate($this->perPage($filters));
    }

    public function create(array $data): Program
    {
        $program = Program::create($data + ['program_code' => app(BusinessNumberService::class)->next('PRG'), 'status' => 'draft', 'created_by' => auth()->id()]);
        $this->audit->record('program_created', $program);

        return $this->find($program->id);
    }

    public function find(string $id): Program
    {
        return Program::with(['budgets', 'enrollments.mustahik', 'periods', 'funds', 'eligibilityRules', 'waitlists', 'activities', 'targets', 'outputs', 'outcomes', 'commitments'])->find($id) ?? throw ZakatException::notFound('Program tidak ditemukan.');
    }

    public function update(Program $program, array $data): Program
    {
        if (! in_array($program->status, ['draft', 'pending_approval'], true)) {
            throw ZakatException::invalidTransition('Program tidak dapat diubah pada status ini.');
        } $program->fill($data)->save();
        $this->audit->record('program_updated', $program);

        return $this->find($program->id);
    }

    public function transition(Program $program, string $to): Program
    {
        $allowed = ['draft' => ['pending_approval', 'cancelled'], 'pending_approval' => ['active', 'cancelled'], 'active' => ['suspended', 'completed', 'cancelled'], 'suspended' => ['active', 'cancelled'], 'completed' => ['closed'], 'closed' => ['archived']];
        if (! in_array($to, $allowed[$program->status] ?? [], true)) {
            throw ZakatException::invalidTransition("Program tidak dapat menjadi {$to} dari {$program->status}.");
        }
        if ($to === 'active' && $program->created_by === auth()->id()) {
            throw ZakatException::forbidden('Maker tidak dapat mengaktifkan program sendiri.');
        }
        $program->forceFill(['status' => $to, 'archived_at' => $to === 'closed' ? now() : $program->archived_at])->saveQuietly();
        $this->audit->record('program_'.str_replace('pending_approval', 'submitted', $to), $program);

        return $this->find($program->id);
    }

    public function categories(array $filters): LengthAwarePaginator
    {
        return ProgramCategory::query()->latest()->paginate($this->perPage($filters));
    }

    public function createCategory(array $data): ProgramCategory
    {
        return ProgramCategory::create($data);
    }

    public function budget(Program $program, array $data): ProgramBudget
    {
        if (! in_array($program->status, ['draft', 'pending_approval'], true)) {
            throw ZakatException::invalidTransition('Budget hanya dapat dibuat sebelum program aktif.');
        } $amount = (string) $data['budget_amount'];
        $budget = ProgramBudget::create($data + ['program_id' => $program->id, 'remaining_amount' => $amount, 'status' => 'draft']);
        $this->audit->record('program_budget_created', $budget);

        return $budget;
    }

    public function budgets(Program $program): mixed
    {
        return $program->budgets()->latest()->get();
    }

    public function updateBudget(ProgramBudget $budget, array $data): ProgramBudget
    {
        if ($budget->status !== 'draft') {
            throw ZakatException::invalidTransition('Budget aktif tidak dapat diubah langsung.');
        } $budget->fill($data)->save();
        $this->audit->record('program_budget_updated', $budget);

        return $budget;
    }

    public function approveBudget(ProgramBudget $budget): ProgramBudget
    {
        if ($budget->status !== 'draft') {
            throw ZakatException::invalidTransition('Budget tidak menunggu approval.');
        } $budget->forceFill(['status' => 'active'])->saveQuietly();
        $this->audit->record('program_budget_approved', $budget);

        return $budget;
    }

    public function enroll(Program $program, array $data): ProgramEnrollment
    {
        if ($program->status !== 'active') {
            throw ZakatException::invalidTransition('Program harus active untuk enrollment.');
        }
        $mustahik = Mustahik::find($data['mustahik_id']) ?? throw ZakatException::notFound('Mustahik tidak ditemukan.');
        if ($program->enrollments()->where('mustahik_id', $mustahik->id)->whereIn('status', ['approved', 'active'])->exists()) {
            throw ZakatException::conflict('Mustahik sudah terdaftar aktif pada program ini.');
        }
        if ($program->capacity_limit && $program->enrollments()->whereIn('status', ['approved', 'active'])->count() >= $program->capacity_limit) {
            if ($program->waitlist_enabled) {
                $waitlist = $this->waitlist($program, $data);
                throw ZakatException::conflict('PROGRAM_FULL_WAITLISTED', ['waitlist_id' => $waitlist->id]);
            }
            throw ZakatException::conflict('PROGRAM_FULL');
        }
        $enrollment = ProgramEnrollment::create(['program_id' => $program->id, 'mustahik_id' => $mustahik->id, 'enrollment_number' => app(BusinessNumberService::class)->next('ENR'), 'eligibility_result' => $data['eligibility_result'] ?? 'pending', 'assessment_id' => $data['assessment_id'] ?? null, 'enrolled_at' => now(), 'enrolled_by' => auth()->id(), 'status' => 'pending', 'notes' => $data['notes'] ?? null]);
        $this->audit->record('program_enrollment_created', $enrollment);

        return $enrollment->load('mustahik');
    }

    public function approveEnrollment(ProgramEnrollment $enrollment): ProgramEnrollment
    {
        if ($enrollment->status !== 'pending') {
            throw ZakatException::invalidTransition('Enrollment tidak menunggu approval.');
        } $enrollment->forceFill(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()])->saveQuietly();
        $this->audit->record('program_enrollment_approved', $enrollment);

        return $enrollment->fresh('mustahik');
    }

    public function enrollments(Program $program): mixed
    {
        return $program->enrollments()->with('mustahik')->latest()->get();
    }

    public function enrollment(string $id): ProgramEnrollment
    {
        return ProgramEnrollment::whereHas('program')->findOrFail($id);
    }

    public function budgetById(string $id): ProgramBudget
    {
        return ProgramBudget::whereHas('program')->findOrFail($id);
    }

    public function evaluation(string $id): ProgramEligibilityEvaluation
    {
        return ProgramEligibilityEvaluation::whereHas('program')->findOrFail($id);
    }

    public function activity(string $id): ProgramActivity
    {
        return ProgramActivity::whereHas('program')->findOrFail($id);
    }

    public function targetById(string $id): ProgramTarget
    {
        return ProgramTarget::whereHas('program')->findOrFail($id);
    }

    public function outputById(string $id): ProgramOutput
    {
        return ProgramOutput::whereHas('program')->findOrFail($id);
    }

    public function outcomeById(string $id): ProgramOutcome
    {
        return ProgramOutcome::whereHas('program')->findOrFail($id);
    }

    public function commitmentById(string $id): ProgramBudgetCommitment
    {
        return ProgramBudgetCommitment::whereHas('program')->findOrFail($id);
    }

    public function rejectEnrollment(ProgramEnrollment $enrollment, string $reason): ProgramEnrollment
    {
        if (! in_array($enrollment->status, ['pending', 'under_review'], true)) {
            throw ZakatException::invalidTransition('Enrollment tidak dapat ditolak.');
        } $enrollment->forceFill(['status' => 'rejected', 'notes' => $reason])->saveQuietly();
        $this->audit->record('program_enrollment_rejected', $enrollment, context: ['reason' => $reason]);

        return $enrollment;
    }

    public function withdrawEnrollment(ProgramEnrollment $enrollment, string $reason): ProgramEnrollment
    {
        if (in_array($enrollment->status, ['completed', 'withdrawn', 'rejected'], true)) {
            throw ZakatException::invalidTransition('Enrollment tidak dapat ditarik.');
        } $enrollment->forceFill(['status' => 'withdrawn', 'notes' => $reason])->saveQuietly();
        $this->audit->record('program_enrollment_withdrawn', $enrollment, context: ['reason' => $reason]);

        return $enrollment;
    }

    public function eligibleMustahiks(Program $program): mixed
    {
        return ProgramEligibilityEvaluation::with('mustahik')->where('program_id', $program->id)->where('result', 'eligible')->latest()->get();
    }

    public function periods(Program $program): mixed
    {
        return $program->periods()->latest('start_date')->get();
    }

    public function createPeriod(Program $program, array $data): ProgramPeriod
    {
        if ($program->status === 'closed') {
            throw ZakatException::invalidTransition('Program closed tidak dapat memiliki period baru.');
        }

        return $program->periods()->create($data + ['status' => 'draft']);
    }

    public function addFund(Program $program, array $data): ProgramFund
    {
        return ProgramFund::firstOrCreate(['program_id' => $program->id, 'fund_id' => $data['fund_id']], ['priority' => $data['priority'] ?? 0, 'status' => 'active']);
    }

    public function rules(Program $program): mixed
    {
        return $program->eligibilityRules()->orderBy('sort_order')->get();
    }

    public function createRule(Program $program, array $data): ProgramEligibilityRule
    {
        return $program->eligibilityRules()->create($data + ['status' => 'active']);
    }

    public function evaluate(Program $program, array $data): ProgramEligibilityEvaluation
    {
        $mustahik = Mustahik::with(['profile', 'asnaf'])->find($data['mustahik_id']) ?? throw ZakatException::notFound('Mustahik tidak ditemukan.');
        $assessment = ! empty($data['assessment_id']) ? Assessment::find($data['assessment_id']) : null;
        $matched = [];
        $score = 0.0;
        $weight = 0.0;
        $failedRequired = false;
        foreach ($program->eligibilityRules()->where('status', 'active')->orderBy('sort_order')->get() as $rule) {
            $actual = $this->ruleValue($mustahik, $assessment, $rule->field);
            $passed = $this->matches($actual, $rule->operator, $rule->value);
            $matched[] = ['rule_code' => $rule->rule_code, 'passed' => $passed, 'actual' => $actual];
            $weight += (float) $rule->weight;
            if ($passed) {
                $score += (float) $rule->weight;
            } elseif ($rule->required) {
                $failedRequired = true;
            }
        }
        $result = $failedRequired ? 'not_eligible' : ($weight === 0.0 || $score === $weight ? 'eligible' : ($score === 0.0 ? 'not_eligible' : 'partially_eligible'));

        return ProgramEligibilityEvaluation::create(['program_id' => $program->id, 'mustahik_id' => $mustahik->id, 'assessment_id' => $assessment?->id, 'result' => $result, 'score' => $weight > 0 ? ($score / $weight) * 100 : 0, 'matched_rules' => $matched, 'evaluated_at' => now(), 'evaluated_by' => auth()->id()]);
    }

    public function overrideEligibility(ProgramEligibilityEvaluation $evaluation, array $data): ProgramEligibilityEvaluation
    {
        $evaluation->forceFill(['result' => $data['result'], 'override_reason' => $data['reason'], 'overridden_by' => auth()->id()])->saveQuietly();
        $this->audit->record('program_eligibility_overridden', $evaluation, context: ['reason' => $data['reason']]);

        return $evaluation;
    }

    public function waitlist(Program $program, array $data): ProgramWaitlist
    {
        $mustahik = Mustahik::find($data['mustahik_id']) ?? throw ZakatException::notFound('Mustahik tidak ditemukan.');
        $position = (int) $program->waitlists()->where('status', 'waiting')->max('position') + 1;
        $item = ProgramWaitlist::create(['program_id' => $program->id, 'mustahik_id' => $mustahik->id, 'assessment_id' => $data['assessment_id'] ?? null, 'priority_score' => $data['priority_score'] ?? 0, 'position' => $position, 'status' => 'waiting', 'added_at' => now()]);
        $this->audit->record('program_waitlist_added', $item);

        return $item;
    }

    public function activities(Program $program): mixed
    {
        return $program->activities()->with('participants')->latest()->get();
    }

    public function createActivity(Program $program, array $data): ProgramActivity
    {
        $activity = $program->activities()->create($data + ['status' => 'draft']);
        $this->audit->record('program_activity_created', $activity);

        return $activity;
    }

    public function updateActivity(ProgramActivity $activity, array $data): ProgramActivity
    {
        $activity->fill($data)->save();
        $this->audit->record('program_activity_updated', $activity);

        return $activity;
    }

    public function participant(ProgramActivity $activity, array $data): ProgramActivityParticipant
    {
        return $activity->participants()->create($data + ['attendance_status' => 'registered', 'participation_status' => 'active']);
    }

    public function target(Program $program, array $data): ProgramTarget
    {
        $target = $program->targets()->create($data + ['current_value' => 0]);
        $this->audit->record('program_target_created', $target);

        return $target;
    }

    public function updateTarget(ProgramTarget $target, array $data): ProgramTarget
    {
        $target->fill($data)->save();
        $this->audit->record('program_target_updated', $target);

        return $target;
    }

    public function output(Program $program, array $data): ProgramOutput
    {
        $output = $program->outputs()->create($data + ['actual_value' => 0, 'status' => 'active']);
        $this->audit->record('program_output_updated', $output);

        return $output;
    }

    public function updateOutput(ProgramOutput $output, array $data): ProgramOutput
    {
        $output->fill($data)->save();
        $this->audit->record('program_output_updated', $output);

        return $output;
    }

    public function outcome(Program $program, array $data): ProgramOutcome
    {
        $outcome = $program->outcomes()->create($data + ['actual_value' => 0, 'status' => 'active']);
        $this->audit->record('program_outcome_updated', $outcome);

        return $outcome;
    }

    public function updateOutcome(ProgramOutcome $outcome, array $data): ProgramOutcome
    {
        $outcome->fill($data)->save();
        $this->audit->record('program_outcome_updated', $outcome);

        return $outcome;
    }

    public function commit(Program $program, array $data): ProgramBudgetCommitment
    {
        $budget = ProgramBudget::where('program_id', $program->id)->find($data['program_budget_id']) ?? throw ZakatException::notFound('Program budget tidak ditemukan.');
        $amount = (string) $data['amount'];
        $available = bcsub(bcsub((string) $budget->budget_amount, (string) $budget->committed_amount, 2), (string) $budget->disbursed_amount, 2);
        if (bccomp($available, $amount, 2) < 0) {
            throw ZakatException::conflict('PROGRAM_BUDGET_EXCEEDED');
        } $commitment = ProgramBudgetCommitment::create(['program_id' => $program->id, 'program_budget_id' => $budget->id, 'enrollment_id' => $data['enrollment_id'] ?? null, 'distribution_id' => $data['distribution_id'] ?? null, 'amount' => $amount, 'currency' => $data['currency'] ?? 'IDR', 'status' => 'committed', 'reason' => $data['reason'] ?? null, 'created_by' => auth()->id(), 'created_at' => now()]);
        $this->refreshBudget($budget);

        return $commitment;
    }

    public function disburse(ProgramBudgetCommitment $commitment): ProgramBudgetCommitment
    {
        if ($commitment->status !== 'committed') {
            throw ZakatException::invalidTransition('Commitment tidak aktif.');
        } $commitment->forceFill(['status' => 'disbursed'])->saveQuietly();
        $this->refreshBudget($commitment->programBudget);

        return $commitment;
    }

    /**
     * Melepas commitment yang batal direalisasikan sehingga budget program kembali
     * tersedia. Dibutuhkan alur cancel dan reverse pada PRD 12U dan 12V.
     */
    public function releaseCommitment(ProgramBudgetCommitment $commitment, string $reason): ProgramBudgetCommitment
    {
        if ($commitment->status === 'released') {
            return $commitment;
        }

        $commitment->forceFill(['status' => 'released', 'reason' => $reason])->saveQuietly();
        $this->refreshBudget($commitment->programBudget);
        $this->audit->record('program_commitment_released', $commitment, context: ['reason' => $reason]);

        return $commitment;
    }

    public function dashboard(): array
    {
        $programs = Program::query();
        $budgets = ProgramBudget::query();

        return ['active_programs' => (clone $programs)->where('status', 'active')->count(), 'completed_programs' => (clone $programs)->where('status', 'completed')->count(), 'total_budget' => (string) (clone $budgets)->sum('budget_amount'), 'committed_budget' => (string) (clone $budgets)->sum('committed_amount'), 'disbursed_amount' => (string) (clone $budgets)->sum('disbursed_amount'), 'remaining_budget' => (string) (clone $budgets)->sum('remaining_amount'), 'target_beneficiaries' => (int) (clone $programs)->sum('target_beneficiary'), 'active_beneficiaries' => ProgramEnrollment::whereIn('status', ['approved', 'active'])->count()];
    }

    private function refreshBudget(ProgramBudget $budget): void
    {
        $committed = (string) $budget->commitments()->where('status', 'committed')->sum('amount');
        $disbursed = (string) $budget->commitments()->where('status', 'disbursed')->sum('amount');
        $remaining = bcsub(bcsub((string) $budget->budget_amount, $committed, 2), $disbursed, 2);
        $budget->forceFill(['committed_amount' => $committed, 'disbursed_amount' => $disbursed, 'remaining_amount' => $remaining, 'status' => bccomp($remaining, '0', 2) <= 0 ? 'exhausted' : $budget->status])->saveQuietly();
    }

    private function ruleValue(Mustahik $mustahik, ?Assessment $assessment, string $field): mixed
    {
        if (str_starts_with($field, 'assessment.')) {
            return $assessment?->{str_replace('assessment.', '', $field)};
        } if (str_starts_with($field, 'profile.')) {
            return $mustahik->profile?->{str_replace('profile.', '', $field)};
        } if ($field === 'asnaf') {
            return $mustahik->asnaf->where('status', 'active')->pluck('asnaf_code')->values()->all();
        } if ($field === 'age' && $mustahik->birth_date) {
            return $mustahik->birth_date->age;
        }

        return $mustahik->{$field};
    }

    private function matches(mixed $actual, string $operator, mixed $expected): bool
    {
        $expected = is_array($expected) && count($expected) === 1 ? $expected[0] : $expected;

        return match ($operator) {
            'equals' => $actual == $expected, 'not_equals' => $actual != $expected, 'greater_than' => $actual > $expected, 'less_than' => $actual < $expected, 'greater_than_or_equal' => $actual >= $expected, 'less_than_or_equal' => $actual <= $expected, 'in' => in_array($actual, (array) $expected, true) || (is_array($actual) && array_intersect($actual, (array) $expected) !== []), 'not_in' => ! $this->matches($actual, 'in', $expected), 'contains' => is_string($actual) ? str_contains($actual, (string) $expected) : (is_array($actual) && in_array($expected, $actual, true)), 'exists' => $expected ? $actual !== null : $actual === null, default => false
        };
    }

    private function perPage(array $filters): int
    {
        return min((int) ($filters['per_page'] ?? 15), (int) config('zakat.pagination.max_per_page'));
    }
}
