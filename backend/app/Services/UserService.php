<?php

namespace App\Services;

use App\Enums\ErrorCode;
use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use App\Exceptions\ZakatException;
use App\Models\OrganizationMember;
use App\Models\Role;
use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\UserInvitationNotification;
use App\Support\OrganizationContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** PRD 01F — user dan access management. */
class UserService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly AuthService $auth,
    ) {}

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $organizationId = OrganizationContext::id();

        return User::query()
            // CLAUDE.md §22 — relasi dimuat di depan, bukan di dalam Resource.
            ->with(['organization:id,code,name', 'roles:id,code,name'])
            ->when($organizationId !== null, fn ($query) => $query->whereIn(
                'id',
                OrganizationMember::query()->acrossOrganizations()
                    ->where('organization_id', $organizationId)
                    ->select('user_id')
            ))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(
                fn ($q) => $q->where('name', 'ilike', "%{$search}%")->orWhere('email', 'ilike', "%{$search}%")
            ))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate($this->perPage($filters));
    }

    /**
     * PRD 22 — object level authorization. User hanya terlihat bila berbagi
     * organisasi dengan context aktif. Tidak ditemukan dilaporkan sebagai 404
     * supaya keberadaan ULID tidak bisa dienumerasi.
     */
    public function findForContext(string $id): User
    {
        $organizationId = OrganizationContext::id();

        $user = User::query()
            ->with(['organization:id,code,name', 'roles:id,code,name'])
            ->where('id', $id)
            ->when($organizationId !== null, fn ($query) => $query->whereHas(
                'memberships',
                fn ($q) => $q->where('organization_id', $organizationId)
            ))
            ->first();

        return $user ?? throw ZakatException::notFound('User tidak ditemukan.');
    }

    /** Platform-admin only lookup used by the impersonation boundary. */
    public function findForImpersonation(string $id): User
    {
        return User::query()->with(['organization:id,code,name', 'roles:id,code,name'])->find($id)
            ?? throw ZakatException::notFound('User tidak ditemukan.');
    }

    /**
     * PRD 01 §7 dan §8 — admin membuat user lalu mengirim invitation.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $roleIds
     */
    public function create(array $data, array $roleIds): User
    {
        $organizationId = OrganizationContext::id();

        return DB::transaction(function () use ($data, $roleIds, $organizationId) {
            $user = new User;
            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'username' => $data['username'] ?? null,
                'phone' => $data['phone'] ?? null,
                // Password acak: user wajib melalui invitation untuk menetapkannya.
                'password' => Str::random(48),
            ]);

            // organization_id ditentukan backend dari context, bukan dari payload.
            $user->organization_id = $organizationId;
            $user->status = UserStatus::Pending;
            $user->save();

            if ($organizationId !== null) {
                $member = new OrganizationMember;
                $member->fill(['member_type' => $data['member_type'] ?? 'employee']);
                $member->organization_id = $organizationId;
                $member->user_id = $user->getKey();
                $member->status = MembershipStatus::Active;
                $member->joined_at = now();
                $member->save();
            }

            $this->syncRoles($user, $roleIds, $organizationId, audit: false);
            $this->sendInvitation($user);

            return $user->load(['organization:id,code,name', 'roles:id,code,name']);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(User $user, array $data): User
    {
        $user->fill($data);
        $user->save();

        return $user->load(['organization:id,code,name', 'roles:id,code,name']);
    }

    public function sendInvitation(User $user): string
    {
        $token = Str::random(64);

        UserInvitation::create([
            'user_id' => $user->getKey(),
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addHours((int) config('zakat.invitation.expires_hours')),
            'created_by' => auth()->id(),
        ]);

        $user->notify(new UserInvitationNotification($token));

        return $token;
    }

    /** PRD 01 §8 — invitation single-use, token dibandingkan sebagai hash. */
    public function acceptInvitation(string $email, string $token, string $password): User
    {
        $invitation = UserInvitation::query()
            ->with('user')
            ->where('token_hash', hash('sha256', $token))
            ->first();

        $valid = $invitation !== null
            && $invitation->isUsable()
            && Str::lower($invitation->user->email) === Str::lower($email);

        if (! $valid) {
            throw new ZakatException(ErrorCode::ValidationError, 'Undangan tidak valid atau sudah kedaluwarsa.', [
                'token' => ['Undangan tidak valid atau sudah kedaluwarsa.'],
            ]);
        }

        return DB::transaction(function () use ($invitation, $password) {
            $user = $invitation->user;

            $user->forceFill([
                'password' => $password,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ])->saveQuietly();

            $invitation->forceFill(['used_at' => now()])->save();

            $this->audit->record('user_activated', $user, context: ['via' => 'invitation'], actorId: $user->getKey());

            return $user;
        });
    }

    // ------------------------------------------------------------ status

    public function changeStatus(User $actor, User $user, UserStatus $status): User
    {
        if ($actor->is($user)) {
            // PRD 01 §49.4 — user tidak boleh menonaktifkan dirinya sendiri.
            throw ZakatException::forbidden('Anda tidak dapat mengubah status akun Anda sendiri.');
        }

        if ($status !== UserStatus::Active) {
            $this->assertNotLastSuperAdmin($user);
        }

        $previous = $user->status;

        $user->forceFill([
            'status' => $status,
            'locked_until' => null,
            'failed_login_attempts' => $status === UserStatus::Active ? 0 : $user->failed_login_attempts,
        ])->saveQuietly();

        // PRD 01 §33 dan §49.10 — session dicabut saat akun tidak lagi aktif.
        if ($status !== UserStatus::Active) {
            $this->auth->revokeOtherSessions($user, null);
        }

        $action = match ($status) {
            UserStatus::Active => $previous === UserStatus::Locked ? 'account_unlocked' : 'user_activated',
            UserStatus::Inactive => 'user_deactivated',
            UserStatus::Suspended => 'user_suspended',
            UserStatus::Locked => 'account_locked',
            UserStatus::Pending => 'user_updated',
        };

        $this->audit->record($action, $user, ['status' => $previous->value], ['status' => $status->value]);

        return $user;
    }

    // ------------------------------------------------------------- roles

    /**
     * PRD 01 §32 — perubahan role dicatat dan berlaku pada organization scope.
     *
     * @param  array<int, string>  $roleIds
     */
    public function syncRoles(User $user, array $roleIds, ?string $organizationId, bool $audit = true): User
    {
        $roles = Role::query()
            ->whereIn('id', $roleIds)
            ->where('is_active', true)
            ->where(function ($query) use ($organizationId) {
                $query->whereNull('organization_id');

                if ($organizationId !== null) {
                    $query->orWhere('organization_id', $organizationId);
                }
            })
            ->get();

        if ($roles->count() !== count(array_unique($roleIds))) {
            // PRD 01 §48 — role harus aktif dan berada pada scope yang valid.
            throw ZakatException::forbidden('Sebagian role tidak valid untuk organisasi ini.');
        }

        $before = $user->roleCodesFor($organizationId);

        DB::transaction(function () use ($user, $roles, $organizationId) {
            // Hanya assignment pada scope ini yang diganti; scope organisasi lain
            // dan assignment platform-level tidak boleh ikut terhapus.
            $user->roles()->wherePivot('organization_id', $organizationId)->detach();

            $user->roles()->attach(
                $roles->mapWithKeys(fn (Role $role) => [$role->getKey() => ['organization_id' => $organizationId]])->all()
            );
        });

        $user->forgetPermissionCache();
        $user->unsetRelation('roles');

        if ($audit) {
            $this->audit->record(
                'role_assigned',
                $user,
                ['roles' => $before],
                ['roles' => $roles->pluck('code')->all()],
                ['organization_id' => $organizationId],
            );
        }

        return $user->load('roles:id,code,name');
    }

    /** PRD 01 §49.5 — sistem harus selalu punya minimal satu Super Admin aktif. */
    private function assertNotLastSuperAdmin(User $user): void
    {
        $isSuperAdmin = $user->roles()->where('roles.code', Role::SUPER_ADMIN)->exists();

        if (! $isSuperAdmin) {
            return;
        }

        $others = User::query()
            ->where('id', '!=', $user->getKey())
            ->where('status', UserStatus::Active)
            ->whereHas('roles', fn ($query) => $query->where('roles.code', Role::SUPER_ADMIN))
            ->count();

        if ($others === 0) {
            throw ZakatException::conflict('Sistem harus memiliki minimal satu Super Admin aktif.');
        }
    }

    /** @param array<string, mixed> $filters */
    private function perPage(array $filters): int
    {
        return min(
            (int) ($filters['per_page'] ?? config('zakat.pagination.per_page')),
            (int) config('zakat.pagination.max_per_page')
        );
    }
}
