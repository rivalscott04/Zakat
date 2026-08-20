<?php

namespace App\Services;

use App\Exceptions\ZakatException;
use App\Models\Permission;
use App\Models\Role;
use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/** PRD 01D dan 01E — role dan permission management. */
class RoleService
{
    public function __construct(private readonly AuditService $audit) {}

    /**
     * Role yang terlihat adalah role system ditambah role milik organisasi aktif.
     *
     * @return Collection<int, Role>
     */
    public function listForContext(): Collection
    {
        $organizationId = OrganizationContext::id();

        return Role::query()
            ->with('permissions:id,name')
            ->where(function ($query) use ($organizationId) {
                $query->whereNull('organization_id');

                if ($organizationId !== null) {
                    $query->orWhere('organization_id', $organizationId);
                }
            })
            ->orderBy('code')
            ->get();
    }

    /** Object level authorization: role hanya terlihat pada scope yang berlaku. */
    public function findForContext(string $id): Role
    {
        $organizationId = OrganizationContext::id();

        $role = Role::query()
            ->with('permissions:id,name')
            ->where('id', $id)
            ->where(function ($query) use ($organizationId) {
                $query->whereNull('organization_id');

                if ($organizationId !== null) {
                    $query->orWhere('organization_id', $organizationId);
                }
            })
            ->first();

        return $role ?? throw ZakatException::notFound('Role tidak ditemukan.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $permissionIds
     */
    public function create(array $data, array $permissionIds): Role
    {
        $organizationId = OrganizationContext::id();

        if ($organizationId === null) {
            throw ZakatException::forbidden('Pembuatan role membutuhkan organization context aktif.');
        }

        return DB::transaction(function () use ($data, $permissionIds, $organizationId) {
            $role = new Role;
            $role->fill($data);
            $role->code = strtoupper($data['code']);
            $role->organization_id = $organizationId;
            // Role system hanya dibuat oleh seeder, tidak lewat API (PRD 01 §49.6).
            $role->is_system = false;
            $role->save();

            $this->syncPermissions($role, $permissionIds, audit: false);

            return $role->load('permissions:id,name');
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Role $role, array $data): Role
    {
        $this->assertMutable($role);

        $role->fill($data);
        // Code role bersifat immutable setelah dibuat.
        $role->code = $role->getOriginal('code');
        $role->save();

        return $role->load('permissions:id,name');
    }

    /** @param array<int, string> $permissionIds */
    public function syncPermissions(Role $role, array $permissionIds, bool $audit = true): Role
    {
        $this->assertMutable($role);

        $permissions = Permission::query()->whereIn('id', $permissionIds)->get();

        if ($permissions->count() !== count(array_unique($permissionIds))) {
            throw new \App\Exceptions\ZakatException(
                \App\Enums\ErrorCode::ValidationError,
                'Sebagian permission tidak dikenal.',
                ['permission_ids' => ['Sebagian permission tidak dikenal.']]
            );
        }

        $before = $role->permissions()->pluck('name')->all();

        $role->permissions()->sync($permissions->pluck('id')->all());

        if ($audit) {
            $this->audit->record(
                'role_permissions_updated',
                $role,
                ['permissions' => $before],
                ['permissions' => $permissions->pluck('name')->all()],
            );
        }

        return $role->load('permissions:id,name');
    }

    /** PRD 01 §39 — permission dan role system tidak boleh diubah lewat API. */
    private function assertMutable(Role $role): void
    {
        if ($role->is_system) {
            throw ZakatException::forbidden('Role system tidak dapat diubah.');
        }

        if ($role->organization_id !== OrganizationContext::id()) {
            throw ZakatException::notFound('Role tidak ditemukan.');
        }
    }
}
