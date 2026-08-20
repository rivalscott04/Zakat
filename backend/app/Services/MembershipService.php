<?php

namespace App\Services;

use App\Enums\MembershipStatus;
use App\Exceptions\ZakatException;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/** PRD 02C — organization membership. */
class MembershipService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly AuthService $auth,
    ) {}

    /** @param array<string, mixed> $filters */
    public function paginate(Organization $organization, array $filters): LengthAwarePaginator
    {
        return OrganizationMember::query()
            ->acrossOrganizations()
            ->where('organization_id', $organization->getKey())
            ->with(['user:id,name,email,status'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('created_at')
            ->paginate(min(
                (int) ($filters['per_page'] ?? config('zakat.pagination.per_page')),
                (int) config('zakat.pagination.max_per_page')
            ));
    }

    /** PRD 02 §36.1 — membership tidak boleh ganda pada organisasi yang sama. */
    public function add(Organization $organization, string $userId, string $memberType): OrganizationMember
    {
        $user = User::query()->find($userId) ?? throw ZakatException::notFound('User tidak ditemukan.');

        $exists = OrganizationMember::query()
            ->acrossOrganizations()
            ->where('organization_id', $organization->getKey())
            ->where('user_id', $user->getKey())
            ->exists();

        if ($exists) {
            throw ZakatException::duplicate('User sudah terdaftar sebagai member organisasi ini.');
        }

        $member = new OrganizationMember;
        $member->fill(['member_type' => $memberType]);
        $member->organization_id = $organization->getKey();
        $member->user_id = $user->getKey();
        $member->status = MembershipStatus::Active;
        $member->joined_at = now();
        $member->save();

        return $member->load('user:id,name,email,status');
    }

    /** @param array<string, mixed> $data */
    public function update(OrganizationMember $member, array $data): OrganizationMember
    {
        $member->fill($data);
        $member->save();

        return $member->load('user:id,name,email,status');
    }

    public function changeStatus(OrganizationMember $member, MembershipStatus $status): OrganizationMember
    {
        if ($member->status === MembershipStatus::Terminated) {
            // PRD 02 §36.2 — membership terminated bersifat final.
            throw ZakatException::invalidTransition('Membership yang sudah terminated tidak dapat diaktifkan kembali.');
        }

        $previous = $member->status;
        $member->loadMissing('user');

        DB::transaction(function () use ($member, $status) {
            $member->status = $status;
            $member->left_at = $status === MembershipStatus::Terminated ? now() : null;
            $member->saveQuietly();

            if ($status !== MembershipStatus::Active) {
                // Membership dicabut berarti seluruh session organisasi tersebut
                // tidak lagi valid untuk user ini.
                $this->auth->revokeOtherSessions($member->user, null);
            }
        });

        $action = match ($status) {
            MembershipStatus::Active => 'organization_member_activated',
            MembershipStatus::Inactive => 'organization_member_deactivated',
            MembershipStatus::Terminated => 'organization_member_terminated',
            MembershipStatus::Pending => 'organization_member_updated',
        };

        $this->audit->record(
            $action,
            $member,
            ['status' => $previous->value],
            ['status' => $status->value],
            organizationId: $member->organization_id,
        );

        return $member->load('user:id,name,email,status');
    }

    public function findInOrganization(Organization $organization, string $memberId): OrganizationMember
    {
        $member = OrganizationMember::query()
            ->acrossOrganizations()
            ->where('organization_id', $organization->getKey())
            ->with('user:id,name,email,status')
            ->find($memberId);

        return $member ?? throw ZakatException::notFound('Member tidak ditemukan.');
    }
}
