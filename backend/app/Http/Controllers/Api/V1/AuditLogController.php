<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuditLogFilterRequest;
use App\Http\Resources\AuditLogResource;
use App\Services\AuditLogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/** PRD 17T — API audit trail. Seluruhnya hanya-baca. */
class AuditLogController extends Controller
{
    public function __construct(private readonly AuditLogService $audits) {}

    public function index(AuditLogFilterRequest $request): JsonResponse
    {
        return ApiResponse::data(AuditLogResource::collection($this->audits->list($request->validated())));
    }

    public function summary(AuditLogFilterRequest $request): JsonResponse
    {
        return ApiResponse::data($this->audits->summary($request->validated()));
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::data(new AuditLogResource($this->audits->find($id), detailed: true));
    }

    public function entity(string $entityType, string $entityId): JsonResponse
    {
        return ApiResponse::data(AuditLogResource::collection($this->audits->forEntity($entityType, $entityId)));
    }

    public function request(string $requestId): JsonResponse
    {
        return ApiResponse::data(AuditLogResource::collection($this->audits->forRequest($requestId)));
    }

    public function export(AuditLogFilterRequest $request): JsonResponse
    {
        return ApiResponse::data($this->audits->export($request->validated()));
    }

    public function integrityCheck(AuditLogFilterRequest $request): JsonResponse
    {
        return ApiResponse::data($this->audits->integrityCheck($request->validated()));
    }
}
