<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\SyncRolesRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\ImpersonationService;
use App\Services\UserService;
use App\Support\ApiResponse;
use App\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** PRD 01 §37 — user endpoints. */
class UserController extends Controller
{
    public function __construct(
        private readonly UserService $users,
        private readonly ImpersonationService $impersonation,
    ) {}

    public function index(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(UserResource::collection($this->users->paginate($request->filters())));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = $this->users->create($data, $data['role_ids']);

        return ApiResponse::data(new UserResource($user), status: 201);
    }

    public function show(string $userId): JsonResponse
    {
        return ApiResponse::data(new UserResource($this->users->findForContext($userId)));
    }

    public function update(UpdateUserRequest $request, string $userId): JsonResponse
    {
        $user = $this->users->findForContext($userId);

        return ApiResponse::data(new UserResource($this->users->update($user, $request->validated())));
    }

    public function syncRoles(SyncRolesRequest $request, string $userId): JsonResponse
    {
        $user = $this->users->findForContext($userId);

        return ApiResponse::data(new UserResource(
            $this->users->syncRoles($user, $request->validated('role_ids'), OrganizationContext::id())
        ));
    }

    // PRD 01 §37 — status action eksplisit, bukan PATCH status (CLAUDE.md §33).

    public function activate(Request $request, string $userId): JsonResponse
    {
        return $this->transition($request, $userId, UserStatus::Active);
    }

    public function deactivate(Request $request, string $userId): JsonResponse
    {
        return $this->transition($request, $userId, UserStatus::Inactive);
    }

    public function suspend(Request $request, string $userId): JsonResponse
    {
        return $this->transition($request, $userId, UserStatus::Suspended);
    }

    public function unlock(Request $request, string $userId): JsonResponse
    {
        return $this->transition($request, $userId, UserStatus::Active);
    }

    public function impersonate(Request $request, string $userId): JsonResponse
    {
        $target = $this->users->findForImpersonation($userId);

        $this->impersonation->start($request, $request->user(), $target);

        return ApiResponse::data(['message' => 'Impersonate dimulai.']);
    }

    private function transition(Request $request, string $userId, UserStatus $status): JsonResponse
    {
        $user = $this->users->findForContext($userId);

        return ApiResponse::data(new UserResource(
            $this->users->changeStatus($request->user(), $user, $status)
        ));
    }
}
