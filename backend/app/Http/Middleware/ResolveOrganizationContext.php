<?php

namespace App\Http\Middleware;

use App\Enums\ErrorCode;
use App\Enums\MembershipStatus;
use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Support\ApiResponse;
use App\Support\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PRD 02 §15 dan §27 — menentukan active organization context.
 *
 * Sumbernya adalah session milik backend, bukan header atau body request.
 * Membership diverifikasi ulang setiap request supaya pencabutan membership
 * langsung berlaku tanpa menunggu session berakhir.
 */
class ResolveOrganizationContext
{
    public const SESSION_KEY = 'active_organization_id';

    public function handle(Request $request, Closure $next): Response
    {
        OrganizationContext::set(null);

        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $organization = $this->resolve($request, $user);

        if ($organization === null) {
            return $next($request);
        }

        // PRD 02 §28 — organisasi suspended tidak boleh membuat perubahan baru.
        // Platform admin tetap boleh masuk untuk investigasi.
        if (! $organization->status->acceptsNewTransactions()
            && ! $request->isMethodSafe()
            && ! $user->isPlatformAdmin()) {
            return ApiResponse::error(
                ErrorCode::Forbidden,
                "Organisasi berstatus {$organization->status->value} tidak dapat melakukan perubahan data."
            );
        }

        OrganizationContext::set($organization);

        return $next($request);
    }

    private function resolve(Request $request, $user): ?Organization
    {
        $selected = $request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null;

        $candidate = $selected ?? $this->defaultOrganizationId($user);

        if ($candidate === null) {
            return null;
        }

        // Membership wajib aktif. Bila dicabut, context dibuang dari session.
        // Platform admin dikecualikan karena bukan anggota organisasi mana pun
        // (PRD 01 §24, PRD 02 §28).
        if (! $user->isPlatformAdmin() && $user->activeMembershipFor($candidate) === null) {
            if ($request->hasSession()) {
                $request->session()->forget(self::SESSION_KEY);
            }

            return null;
        }

        return Organization::query()
            ->where('id', $candidate)
            ->where('status', '!=', OrganizationStatus::Archived)
            ->first();
    }

    private function defaultOrganizationId($user): ?string
    {
        if ($user->organization_id !== null && $user->activeMembershipFor($user->organization_id) !== null) {
            return $user->organization_id;
        }

        return $user->memberships()
            ->where('status', MembershipStatus::Active)
            ->orderBy('created_at')
            ->value('organization_id');
    }
}
