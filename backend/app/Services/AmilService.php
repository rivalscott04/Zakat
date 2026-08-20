<?php

namespace App\Services;

use App\Enums\AmilStatus;
use App\Enums\AssignmentStatus;
use App\Enums\MembershipStatus;
use App\Exceptions\ZakatException;
use App\Models\Amil;
use App\Models\AmilAssignment;
use App\Models\OrganizationMember;
use App\Support\OrganizationContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/** PRD 02D — amil dan assignment. */
class AmilService
{
    public function __construct(private readonly AuditService $audit) {}

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Amil::query()
            ->with(['user:id,name,email,status', 'activeAssignments'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(
                fn ($q) => $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('business_number', 'ilike', "%{$search}%")
                    ->orWhere('employee_number', 'ilike', "%{$search}%")
            ))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(min(
                (int) ($filters['per_page'] ?? config('zakat.pagination.per_page')),
                (int) config('zakat.pagination.max_per_page')
            ));
    }

    /** Global scope organisasi sudah menjamin amil dari organisasi lain tidak terlihat. */
    public function findInContext(string $id): Amil
    {
        $amil = Amil::query()->with(['user:id,name,email,status', 'assignments'])->find($id);

        return $amil ?? throw ZakatException::notFound('Amil tidak ditemukan.');
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Amil
    {
        $organizationId = OrganizationContext::requireId();
        $userId = $data['user_id'] ?? null;

        if ($userId !== null) {
            $this->assertLinkableUser($organizationId, $userId);
        }

        return DB::transaction(function () use ($data, $organizationId, $userId) {
            $amil = new Amil;
            $amil->fill(collect($data)->only(['name', 'employee_number', 'email', 'phone', 'joined_at'])->all());
            $amil->organization_id = $organizationId;
            $amil->user_id = $userId;
            $amil->status = AmilStatus::Active;
            $amil->joined_at = $data['joined_at'] ?? now();
            $amil->save();

            return $amil->load('user:id,name,email,status');
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Amil $amil, array $data, bool $userProvided, ?string $userId): Amil
    {
        if ($userProvided && $userId !== $amil->user_id) {
            if ($userId !== null) {
                $this->assertLinkableUser($amil->organization_id, $userId, $amil->getKey());
            }

            $amil->user_id = $userId;
        }

        $amil->fill($data);
        $amil->save();

        return $amil->load('user:id,name,email,status');
    }

    public function changeStatus(Amil $amil, AmilStatus $status): Amil
    {
        if ($amil->status === AmilStatus::Ended && $status !== AmilStatus::Ended) {
            throw ZakatException::invalidTransition('Amil yang sudah ended tidak dapat diaktifkan kembali.');
        }

        $previous = $amil->status;

        DB::transaction(function () use ($amil, $status) {
            $amil->status = $status;
            $amil->ended_at = $status === AmilStatus::Ended ? now() : null;
            $amil->saveQuietly();

            // PRD 02 §37.4 — amil ended tidak lagi memegang assignment aktif.
            if ($status === AmilStatus::Ended) {
                AmilAssignment::query()
                    ->where('amil_id', $amil->getKey())
                    ->where('status', AssignmentStatus::Active)
                    ->update(['status' => AssignmentStatus::Ended->value, 'ended_at' => now(), 'updated_at' => now()]);
            }
        });

        $action = match ($status) {
            AmilStatus::Active => 'amil_activated',
            AmilStatus::Inactive => 'amil_deactivated',
            AmilStatus::Suspended => 'amil_suspended',
            AmilStatus::Ended => 'amil_ended',
        };

        $this->audit->record($action, $amil, ['status' => $previous->value], ['status' => $status->value]);

        return $amil;
    }

    // ------------------------------------------------------------ assignment

    /** @param array<string, mixed> $data */
    public function assign(Amil $amil, array $data): AmilAssignment
    {
        if (! $amil->status->canReceiveAssignment()) {
            throw ZakatException::conflict("Amil berstatus {$amil->status->value} tidak dapat menerima assignment baru.");
        }

        $duplicate = AmilAssignment::query()
            ->where('amil_id', $amil->getKey())
            ->where('assignment_type', $data['assignment_type'])
            ->where('status', AssignmentStatus::Active)
            ->exists();

        if ($duplicate) {
            throw ZakatException::duplicate('Assignment dengan tipe tersebut sudah aktif untuk amil ini.');
        }

        $assignment = new AmilAssignment;
        $assignment->fill(collect($data)->only(['assignment_type', 'started_at'])->all());
        $assignment->amil_id = $amil->getKey();
        $assignment->organization_id = $amil->organization_id;
        $assignment->status = AssignmentStatus::Active;
        $assignment->started_at = $data['started_at'] ?? now();
        $assignment->save();

        return $assignment;
    }

    public function endAssignment(AmilAssignment $assignment): AmilAssignment
    {
        if ($assignment->status === AssignmentStatus::Ended) {
            throw ZakatException::invalidTransition('Assignment sudah berakhir.');
        }

        $assignment->status = AssignmentStatus::Ended;
        $assignment->ended_at = now();
        $assignment->saveQuietly();

        // PRD 02 §37.5 — histori assignment tidak dihapus, hanya diakhiri.
        $this->audit->record('amil_assignment_ended', $assignment, ['status' => AssignmentStatus::Active->value], ['status' => AssignmentStatus::Ended->value]);

        return $assignment;
    }

    public function findAssignment(string $id): AmilAssignment
    {
        return AmilAssignment::query()->find($id) ?? throw ZakatException::notFound('Assignment tidak ditemukan.');
    }

    /** PRD 02 §37.3 — satu user hanya boleh punya satu amil aktif per organisasi. */
    private function assertLinkableUser(string $organizationId, string $userId, ?string $exceptAmilId = null): void
    {
        $isMember = OrganizationMember::query()
            ->acrossOrganizations()
            ->where('organization_id', $organizationId)
            ->where('user_id', $userId)
            ->where('status', MembershipStatus::Active)
            ->exists();

        if (! $isMember) {
            throw ZakatException::conflict('User harus menjadi member aktif organisasi sebelum dikaitkan sebagai amil.');
        }

        $taken = Amil::query()
            ->where('user_id', $userId)
            ->where('status', AmilStatus::Active)
            ->when($exceptAmilId !== null, fn ($query) => $query->where('id', '!=', $exceptAmilId))
            ->exists();

        if ($taken) {
            throw ZakatException::duplicate('User tersebut sudah memiliki data amil aktif pada organisasi ini.');
        }
    }
}
