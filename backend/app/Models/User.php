<?php

namespace App\Models;

use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use App\Models\Concerns\Auditable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

/** PRD 01 §5 — user entity. */
// organization_id dan status sengaja tidak fillable (CLAUDE.md §34): keduanya
// hanya boleh ditentukan Service Layer, tidak pernah dari payload client.
#[Fillable(['name', 'email', 'username', 'password', 'phone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, HasUlids, Notifiable, SoftDeletes;

    /** @var array<string, array<int, string>>|null */
    private ?array $permissionCache = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withPivot('organization_id')->withTimestamps();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(UserInvitation::class);
    }

    // ---------------------------------------------------------------- status

    /** PRD 01 §21 — lock otomatis berakhir sendiri, tidak butuh intervensi admin. */
    public function releaseExpiredLock(): void
    {
        if ($this->status === UserStatus::Locked && $this->locked_until?->isPast()) {
            $this->forceFill([
                'status' => UserStatus::Active,
                'locked_until' => null,
                'failed_login_attempts' => 0,
            ])->save();

            $this->recordAudit('account_unlocked', null, null, ['reason' => 'lock_expired']);
        }
    }

    public function canLogin(): bool
    {
        return $this->status->canLogin();
    }

    // --------------------------------------------------------- authorization

    /**
     * PRD 01 §28 — permission efektif user pada satu organization context.
     * Role assignment tanpa organization_id berlaku lintas organisasi.
     *
     * @return array<int, string>
     */
    public function permissionsFor(?string $organizationId): array
    {
        $key = $organizationId ?? '__global__';

        return $this->permissionCache[$key] ??= DB::table('permissions as p')
            ->join('permission_role as pr', 'pr.permission_id', '=', 'p.id')
            ->join('roles as r', 'r.id', '=', 'pr.role_id')
            ->join('role_user as ru', 'ru.role_id', '=', 'r.id')
            ->where('ru.user_id', $this->getKey())
            ->where('r.is_active', true)
            ->where(function ($query) use ($organizationId) {
                $query->whereNull('ru.organization_id');

                if ($organizationId !== null) {
                    $query->orWhere('ru.organization_id', $organizationId);
                }
            })
            ->distinct()
            ->pluck('p.name')
            ->all();
    }

    public function hasPermissionTo(string $permission, ?string $organizationId = null): bool
    {
        return in_array($permission, $this->permissionsFor($organizationId), true);
    }

    /**
     * PRD 02 §28 — platform administrator boleh mengakses organisasi suspended
     * untuk investigasi. Ditandai oleh role SUPER_ADMIN yang di-assign tanpa
     * organization scope.
     */
    public function isPlatformAdmin(): bool
    {
        return $this->roles()
            ->wherePivotNull('organization_id')
            ->where('roles.code', Role::SUPER_ADMIN)
            ->where('roles.is_active', true)
            ->exists();
    }

    /** @return array<int, string> */
    public function roleCodesFor(?string $organizationId): array
    {
        return $this->roles()
            ->where('roles.is_active', true)
            ->where(function ($query) use ($organizationId) {
                $query->whereNull('role_user.organization_id');

                if ($organizationId !== null) {
                    $query->orWhere('role_user.organization_id', $organizationId);
                }
            })
            ->pluck('roles.code')
            ->all();
    }

    /** PRD 02 §27.3 — akses hanya lewat membership aktif. */
    public function activeMembershipFor(string $organizationId): ?OrganizationMember
    {
        // Global scope organisasi dilepas: pengecekan ini justru yang menentukan
        // organisasi mana yang boleh menjadi context.
        return $this->memberships()
            ->acrossOrganizations()
            ->where('organization_id', $organizationId)
            ->where('status', MembershipStatus::Active)
            ->first();
    }

    public function forgetPermissionCache(): void
    {
        $this->permissionCache = null;
    }
}
