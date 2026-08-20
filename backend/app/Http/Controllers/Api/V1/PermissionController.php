<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/** PRD 01 §39 — katalog permission bersifat read only. */
class PermissionController extends Controller
{
    public function index(): JsonResponse
    {
        $permissions = Permission::query()->orderBy('module')->orderBy('resource')->orderBy('action')->get();

        return ApiResponse::data(PermissionResource::collection($permissions));
    }
}
