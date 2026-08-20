<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\SyncPermissionsRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/** PRD 01 §38 — role endpoints. */
class RoleController extends Controller
{
    public function __construct(private readonly RoleService $roles) {}

    public function index(): JsonResponse
    {
        return ApiResponse::data(RoleResource::collection($this->roles->listForContext()));
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $data = $request->validated();

        $role = $this->roles->create(
            collect($data)->only(['name', 'code', 'description', 'is_active'])->all(),
            $data['permission_ids'],
        );

        return ApiResponse::data(new RoleResource($role), status: 201);
    }

    public function show(string $roleId): JsonResponse
    {
        return ApiResponse::data(new RoleResource($this->roles->findForContext($roleId)));
    }

    public function update(UpdateRoleRequest $request, string $roleId): JsonResponse
    {
        $role = $this->roles->findForContext($roleId);

        return ApiResponse::data(new RoleResource($this->roles->update($role, $request->validated())));
    }

    public function syncPermissions(SyncPermissionsRequest $request, string $roleId): JsonResponse
    {
        $role = $this->roles->findForContext($roleId);

        return ApiResponse::data(new RoleResource(
            $this->roles->syncPermissions($role, $request->validated('permission_ids'))
        ));
    }
}
