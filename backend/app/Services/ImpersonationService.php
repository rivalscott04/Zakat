<?php

namespace App\Services;

use App\Enums\ErrorCode;
use App\Enums\MembershipStatus;
use App\Exceptions\ZakatException;
use App\Http\Middleware\ResolveOrganizationContext;
use App\Models\User;
use Illuminate\Http\Request;
use Lab404\Impersonate\Services\ImpersonateManager;

/** Impersonate user untuk investigasi support (platform admin). */
class ImpersonationService
{
    private const PREVIOUS_ORG_SESSION_KEY = 'impersonation_previous_org_id';

    public function __construct(
        private readonly AuditService $audit,
        private readonly ImpersonateManager $impersonate,
    ) {}

    public function start(Request $request, User $actor, User $target): User
    {
        $this->assertCanStart($actor, $target);

        $request->session()->put(self::PREVIOUS_ORG_SESSION_KEY, $request->session()->get(ResolveOrganizationContext::SESSION_KEY));

        if (! $actor->impersonate($target)) {
            $request->session()->forget(self::PREVIOUS_ORG_SESSION_KEY);

            throw new ZakatException(ErrorCode::ServerError, 'Impersonate gagal.');
        }

        $this->syncOrganizationContext($request, $target);

        $this->audit->record('impersonation_started', $target, context: [
            'impersonator_id' => $actor->getKey(),
            'impersonator_email' => $actor->email,
            'target_id' => $target->getKey(),
            'target_email' => $target->email,
        ], actorId: $actor->getKey());

        return $target->fresh();
    }

    public function leave(Request $request): User
    {
        $impersonated = $request->user();

        if ($impersonated === null || ! $impersonated->isImpersonated()) {
            throw ZakatException::conflict('Sesi impersonate tidak aktif.');
        }

        $impersonator = $this->impersonate->getImpersonator();

        if ($impersonator === null) {
            throw new ZakatException(ErrorCode::ServerError, 'Data impersonator tidak ditemukan.');
        }

        $impersonated->leaveImpersonation();

        $previousOrg = $request->session()->pull(self::PREVIOUS_ORG_SESSION_KEY);

        if ($previousOrg !== null) {
            $request->session()->put(ResolveOrganizationContext::SESSION_KEY, $previousOrg);
        } else {
            $request->session()->forget(ResolveOrganizationContext::SESSION_KEY);
        }

        $this->audit->record('impersonation_ended', $impersonated, context: [
            'impersonator_id' => $impersonator->getKey(),
            'impersonator_email' => $impersonator->email,
            'target_id' => $impersonated->getKey(),
            'target_email' => $impersonated->email,
        ], actorId: $impersonator->getKey());

        return $impersonator->fresh();
    }

    /** @return array{active: bool, impersonator: array{id: string, name: string, email: string}|null} */
    public function statusFor(User $user): array
    {
        if (! $user->isImpersonated()) {
            return ['active' => false, 'impersonator' => null];
        }

        $impersonator = $this->impersonate->getImpersonator();

        if ($impersonator === null) {
            return ['active' => true, 'impersonator' => null];
        }

        return [
            'active' => true,
            'impersonator' => [
                'id' => $impersonator->getKey(),
                'name' => $impersonator->name,
                'email' => $impersonator->email,
            ],
        ];
    }

    private function assertCanStart(User $actor, User $target): void
    {
        if (! $actor->isPlatformAdmin() || ! $actor->canImpersonate()) {
            throw ZakatException::forbidden('Anda tidak memiliki izin impersonate.');
        }

        if ($actor->is($target)) {
            throw ZakatException::conflict('Tidak dapat impersonate diri sendiri.');
        }

        if ($actor->isImpersonated()) {
            throw ZakatException::conflict('Akhiri sesi impersonate yang aktif terlebih dahulu.');
        }

        if (! $target->canBeImpersonated()) {
            throw ZakatException::forbidden('User ini tidak dapat di-impersonate.');
        }
    }

    private function syncOrganizationContext(Request $request, User $target): void
    {
        $organizationId = $target->organization_id;

        if ($organizationId === null || $target->activeMembershipFor($organizationId) === null) {
            $organizationId = $target->memberships()
                ->where('status', MembershipStatus::Active)
                ->orderBy('created_at')
                ->value('organization_id');
        }

        if ($organizationId === null) {
            $request->session()->forget(ResolveOrganizationContext::SESSION_KEY);

            return;
        }

        $request->session()->put(ResolveOrganizationContext::SESSION_KEY, $organizationId);
    }
}
