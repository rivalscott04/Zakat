<?php

namespace App\Notifications;

use App\Enums\MembershipStatus;
use App\Enums\NotificationRecipientStrategy;
use App\Enums\UserStatus;
use App\Models\NotificationRule;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * PRD 16D §8 dan PRD 16M §29 — menentukan siapa yang menerima notification.
 *
 * Hasilnya selalu user yang masih aktif dan masih anggota organisasi terkait,
 * supaya PRD 16Y §15 (tidak ada notification lintas organisasi) tetap terjaga.
 *
 * @return array<int, string> daftar user id
 */
class RecipientResolver
{
    /** @param array<string, mixed> $payload */
    public function resolve(NotificationRule $rule, string $organizationId, array $payload): array
    {
        $config = $rule->recipient_config ?? [];

        $ids = match ($rule->recipient_strategy) {
            NotificationRecipientStrategy::User,
            NotificationRecipientStrategy::Custom => (array) ($config['user_ids'] ?? []),

            NotificationRecipientStrategy::Role => $this->byRoleCodes((array) ($config['roles'] ?? []), $organizationId),

            NotificationRecipientStrategy::OrganizationAdmin => $this->byRoleCodes(['ADMIN'], $organizationId),

            NotificationRecipientStrategy::Permission => $this->byPermissions((array) ($config['permissions'] ?? []), $organizationId),

            // PRD 16M §29 — pemilik data sumber, dikirim modul pemicu lewat payload.
            NotificationRecipientStrategy::SourceOwner => array_filter([
                $payload['owner_id'] ?? $payload['created_by'] ?? null,
            ]),
        };

        return $this->activeMembers(array_values(array_unique(array_map('strval', $ids))), $organizationId);
    }

    /** @param array<int, string> $codes */
    private function byRoleCodes(array $codes, string $organizationId): array
    {
        if ($codes === []) {
            return [];
        }

        return DB::table('role_user as ru')
            ->join('roles as r', 'r.id', '=', 'ru.role_id')
            ->whereIn('r.code', $codes)
            ->where('r.is_active', true)
            ->where(fn ($query) => $query->whereNull('ru.organization_id')->orWhere('ru.organization_id', $organizationId))
            ->distinct()
            ->pluck('ru.user_id')
            ->all();
    }

    /** @param array<int, string> $permissions */
    private function byPermissions(array $permissions, string $organizationId): array
    {
        if ($permissions === []) {
            return [];
        }

        return DB::table('role_user as ru')
            ->join('roles as r', 'r.id', '=', 'ru.role_id')
            ->join('permission_role as pr', 'pr.role_id', '=', 'r.id')
            ->join('permissions as p', 'p.id', '=', 'pr.permission_id')
            ->whereIn('p.name', $permissions)
            ->where('r.is_active', true)
            ->where(fn ($query) => $query->whereNull('ru.organization_id')->orWhere('ru.organization_id', $organizationId))
            ->distinct()
            ->pluck('ru.user_id')
            ->all();
    }

    /**
     * @param  array<int, string>  $userIds
     * @return array<int, string>
     */
    private function activeMembers(array $userIds, string $organizationId): array
    {
        if ($userIds === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->where('status', UserStatus::Active)
            ->whereHas('memberships', fn ($query) => $query
                ->where('organization_id', $organizationId)
                ->where('status', MembershipStatus::Active))
            ->pluck('id')
            ->all();
    }
}
