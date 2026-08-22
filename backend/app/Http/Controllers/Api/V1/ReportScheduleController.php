<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EntityStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListRequest;
use App\Http\Requests\StoreReportScheduleRequest;
use App\Http\Resources\ReportScheduleResource;
use App\Services\ReportScheduleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/** PRD 19R §47. */
class ReportScheduleController extends Controller
{
    public function __construct(private readonly ReportScheduleService $schedules) {}

    public function index(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(ReportScheduleResource::collection($this->schedules->list($request->validated())));
    }

    public function store(StoreReportScheduleRequest $request): JsonResponse
    {
        return ApiResponse::data(new ReportScheduleResource($this->schedules->create($request->validated())), status: 201);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::data(new ReportScheduleResource($this->schedules->find($id)));
    }

    public function update(StoreReportScheduleRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new ReportScheduleResource(
            $this->schedules->update($this->schedules->find($id), $request->validated())
        ));
    }

    public function activate(string $id): JsonResponse
    {
        return ApiResponse::data(new ReportScheduleResource(
            $this->schedules->setStatus($this->schedules->find($id), EntityStatus::Active)
        ));
    }

    public function deactivate(string $id): JsonResponse
    {
        return ApiResponse::data(new ReportScheduleResource(
            $this->schedules->setStatus($this->schedules->find($id), EntityStatus::Inactive)
        ));
    }

    public function runNow(string $id): JsonResponse
    {
        return ApiResponse::data(new ReportScheduleResource($this->schedules->runNow($this->schedules->find($id))));
    }
}
