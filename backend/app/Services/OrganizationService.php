<?php

namespace App\Services;

use App\Enums\MembershipStatus;
use App\Enums\OrganizationStatus;
use App\Exceptions\ZakatException;
use App\Http\Middleware\ResolveOrganizationContext;
use App\Models\Organization;
use App\Models\User;
use App\Support\OrganizationContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** PRD 02A sampai 02C dan 02G — organisasi, hierarki, dan konteks aktif. */
class OrganizationService
{
    public function __construct(private readonly AuditService $audit) {}

    /**
     * PRD 02 §26 — organisasi yang boleh dipakai user sebagai active context.
     *
     * @return Collection<int, Organization>
     */
    public function availableFor(User $user): Collection
    {
        return Organization::query()
            ->whereIn('id', $this->membershipOrganizationIds($user))
            ->where('status', '!=', OrganizationStatus::Archived)
            ->orderBy('name')
            ->get();
    }

    /** PRD 02 §26 — backend memverifikasi membership sebelum context berpindah. */
    public function switchTo(Request $request, User $user, string $organizationId): Organization
    {
        $membership = $user->activeMembershipFor($organizationId);

        if ($membership === null) {
            throw ZakatException::forbidden('Anda tidak memiliki membership aktif pada organisasi tersebut.');
        }

        $organization = Organization::query()->find($organizationId);

        if ($organization === null || $organization->status === OrganizationStatus::Archived) {
            throw ZakatException::notFound('Organisasi tidak ditemukan.');
        }

        $request->session()->put(ResolveOrganizationContext::SESSION_KEY, $organization->getKey());
        OrganizationContext::set($organization);

        $this->audit->record('organization_switched', $organization, organizationId: $organization->getKey());

        return $organization;
    }

    /** @param array<string, mixed> $filters */
    public function paginate(User $user, array $filters): LengthAwarePaginator
    {
        return $this->visibleQuery($user)
            ->with(['parent:id,code,name'])
            ->withCount('children')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(
                fn ($q) => $q->where('name', 'ilike', "%{$search}%")->orWhere('code', 'ilike', "%{$search}%")
            ))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(min(
                (int) ($filters['per_page'] ?? config('zakat.pagination.per_page')),
                (int) config('zakat.pagination.max_per_page')
            ));
    }

    public function findForUser(User $user, string $id): Organization
    {
        $organization = $this->visibleQuery($user)
            ->with(['parent:id,code,name', 'addresses', 'contacts'])
            ->withCount(['children', 'members', 'amils'])
            ->where('id', $id)
            ->first();

        return $organization ?? throw ZakatException::notFound('Organisasi tidak ditemukan.');
    }

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data, ?string $parentId): Organization
    {
        $parent = $parentId === null ? null : $this->findForUser($actor, $parentId);

        if ($parent !== null && $parent->status === OrganizationStatus::Archived) {
            // PRD 02 §38.4.
            throw ZakatException::conflict('Organisasi archived tidak dapat menerima child baru.');
        }

        return DB::transaction(function () use ($data, $parent) {
            $organization = new Organization;
            $organization->fill($data);
            $organization->code = strtoupper($data['code']);
            $organization->parent_id = $parent?->getKey();
            $organization->status = OrganizationStatus::Draft;
            $organization->save();

            return $organization;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Organization $organization, array $data, bool $parentProvided, ?string $parentId): Organization
    {
        if ($parentProvided) {
            $this->assertValidParent($organization, $parentId);
            $organization->parent_id = $parentId;
        }

        // PRD 02 §35 — code immutable setelah organisasi tidak lagi draft.
        if (array_key_exists('code', $data)) {
            if ($organization->status !== OrganizationStatus::Draft) {
                unset($data['code']);
            } else {
                $data['code'] = strtoupper($data['code']);
            }
        }

        $organization->fill($data);
        $organization->save();

        return $organization->load(['parent:id,code,name']);
    }

    public function changeStatus(Organization $organization, OrganizationStatus $status): Organization
    {
        $previous = $organization->status;

        if ($previous === $status) {
            return $organization;
        }

        if ($previous === OrganizationStatus::Archived) {
            throw ZakatException::invalidTransition('Organisasi archived tidak dapat diubah statusnya.');
        }

        $organization->status = $status;
        $organization->saveQuietly();

        $action = match ($status) {
            OrganizationStatus::Active => 'organization_activated',
            OrganizationStatus::Inactive => 'organization_deactivated',
            OrganizationStatus::Suspended => 'organization_suspended',
            OrganizationStatus::Archived => 'organization_archived',
            OrganizationStatus::Draft => 'organization_updated',
        };

        $this->audit->record(
            $action,
            $organization,
            ['status' => $previous->value],
            ['status' => $status->value],
            organizationId: $organization->getKey(),
        );

        return $organization;
    }

    /** @return Collection<int, Organization> */
    public function children(User $user, string $id): Collection
    {
        $parent = $this->findForUser($user, $id);

        return Organization::query()
            ->where('parent_id', $parent->getKey())
            ->orderBy('name')
            ->get();
    }

    // ----------------------------------------------------------- hierarchy

    /** PRD 02 §9 dan §38 — cegah self parent dan circular relationship. */
    private function assertValidParent(Organization $organization, ?string $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        if ($parentId === $organization->getKey()) {
            throw ZakatException::conflict('Organisasi tidak boleh menjadi parent dirinya sendiri.');
        }

        $parent = Organization::query()->find($parentId);

        if ($parent === null) {
            throw ZakatException::notFound('Parent organization tidak ditemukan.');
        }

        if ($parent->status === OrganizationStatus::Archived) {
            throw ZakatException::conflict('Organisasi archived tidak dapat menerima child baru.');
        }

        // Circular terjadi bila organisasi ini sudah menjadi salah satu ancestor parent baru.
        if (in_array($organization->getKey(), $parent->ancestorIds(), true)) {
            throw ZakatException::conflict('Perubahan parent akan membentuk hierarki melingkar.');
        }
    }

    /** @return Builder<Organization> */
    private function visibleQuery(User $user): Builder
    {
        if ($user->isPlatformAdmin()) {
            return Organization::query();
        }

        // Organisasi tempat user menjadi member, beserta seluruh turunannya.
        $roots = $this->membershipOrganizationIds($user);

        if ($roots === []) {
            return Organization::query()->whereRaw('false');
        }

        $ids = DB::select(
            'WITH RECURSIVE tree AS (
                 SELECT id FROM organizations WHERE id = ANY(?) AND deleted_at IS NULL
                 UNION
                 SELECT o.id FROM organizations o JOIN tree t ON o.parent_id = t.id WHERE o.deleted_at IS NULL
             )
             SELECT id FROM tree',
            ['{'.implode(',', $roots).'}']
        );

        return Organization::query()->whereIn('id', array_column($ids, 'id'));
    }

    /** @return array<int, string> */
    private function membershipOrganizationIds(User $user): array
    {
        return $user->memberships()
            ->acrossOrganizations()
            ->where('status', MembershipStatus::Active)
            ->pluck('organization_id')
            ->all();
    }
}
