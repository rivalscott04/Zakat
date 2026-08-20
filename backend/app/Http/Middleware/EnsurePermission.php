<?php

namespace App\Http\Middleware;

use App\Enums\ErrorCode;
use App\Support\ApiResponse;
use App\Support\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PRD 01 §27 — permission enforcement wajib di backend.
 * Dipakai sebagai `permission:users.view` atau `permission:users.view,users.update`
 * (user cukup memiliki salah satu permission yang disebut).
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::error(ErrorCode::Unauthorized, 'Autentikasi diperlukan.');
        }

        $organizationId = OrganizationContext::id();

        foreach ($permissions as $permission) {
            if ($user->hasPermissionTo($permission, $organizationId)) {
                return $next($request);
            }
        }

        return ApiResponse::error(ErrorCode::Forbidden, 'Anda tidak memiliki izin untuk aksi ini.');
    }
}
