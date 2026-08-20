<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptInvitationRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\OrganizationSummaryResource;
use App\Http\Resources\RoleResource;
use App\Services\AuthService;
use App\Services\ImpersonationService;
use App\Services\UserService;
use App\Support\ApiResponse;
use App\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** PRD 01 §35 — authentication endpoints. */
class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly ImpersonationService $impersonation,
        private readonly UserService $users,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        // PRD 01 §9 — login menerima email atau username.
        $this->auth->login($request, $data['login'], $data['password'], (bool) ($data['remember'] ?? false));

        return ApiResponse::data(['authenticated' => true]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request);

        return ApiResponse::data(['authenticated' => false]);
    }

    /** PRD 01 §12 — identitas, organisasi aktif, role, dan permission user. */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['organization:id,code,name']);
        $organization = OrganizationContext::current();

        return ApiResponse::data([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'status' => $user->status->value,
            'home_organization' => $user->organization
                ? new OrganizationSummaryResource($user->organization)
                : null,
            'organization' => $organization
                ? new OrganizationSummaryResource($organization)
                : null,
            'roles' => RoleResource::collection(
                $user->roles()->where('roles.is_active', true)->get()
            ),
            // Permission dikirim hanya untuk kebutuhan UX frontend (PRD 01 §27).
            'permissions' => $user->permissionsFor($organization?->getKey()),
            'impersonation' => $this->impersonation->statusFor($user),
        ]);
    }

    public function leaveImpersonation(Request $request): JsonResponse
    {
        $this->impersonation->leave($request);

        return ApiResponse::data(['message' => 'Impersonate diakhiri.']);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->auth->sendPasswordResetLink($request->validated('email'));

        // PRD 01 §44 — response selalu generik.
        return ApiResponse::data([
            'message' => 'Jika alamat email terdaftar, instruksi reset password akan dikirim.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->auth->resetPassword($request->only('email', 'password', 'password_confirmation', 'token'));

        return ApiResponse::data(['message' => 'Password berhasil diperbarui.']);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->auth->changePassword(
            $request,
            $request->user(),
            $request->validated('current_password'),
            $request->validated('password'),
        );

        return ApiResponse::data(['message' => 'Password berhasil diubah.']);
    }

    public function acceptInvitation(AcceptInvitationRequest $request): JsonResponse
    {
        $this->users->acceptInvitation(
            $request->validated('email'),
            $request->validated('token'),
            $request->validated('password'),
        );

        return ApiResponse::data(['message' => 'Akun berhasil diaktifkan. Silakan login.']);
    }
}
