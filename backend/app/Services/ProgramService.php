<?php

namespace App\Services;

use App\Exceptions\ZakatException;
use App\Models\Mustahik;
use App\Models\Program;
use App\Models\ProgramBudget;
use App\Models\ProgramCategory;
use App\Models\ProgramEnrollment;
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
        return Program::with(['budgets', 'enrollments.mustahik'])->find($id) ?? throw ZakatException::notFound('Program tidak ditemukan.');
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
        $allowed = ['draft' => ['pending_approval', 'cancelled'], 'pending_approval' => ['active', 'cancelled'], 'active' => ['suspended', 'completed', 'cancelled'], 'suspended' => ['active', 'cancelled'], 'completed' => ['closed']];
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

    private function perPage(array $filters): int
    {
        return min((int) ($filters['per_page'] ?? 15), (int) config('zakat.pagination.max_per_page'));
    }
}
