<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EntityStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListRequest;
use App\Http\Requests\StoreReportTemplateRequest;
use App\Http\Resources\ReportTemplateResource;
use App\Services\ReportTemplateService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/** PRD 19R §46. */
class ReportTemplateController extends Controller
{
    public function __construct(private readonly ReportTemplateService $templates) {}

    public function index(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(ReportTemplateResource::collection($this->templates->list($request->validated())));
    }

    public function store(StoreReportTemplateRequest $request): JsonResponse
    {
        return ApiResponse::data(new ReportTemplateResource($this->templates->create($request->validated())), status: 201);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::data(new ReportTemplateResource($this->templates->find($id)));
    }

    public function update(StoreReportTemplateRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new ReportTemplateResource(
            $this->templates->update($this->templates->find($id), $request->validated())
        ));
    }

    public function activate(string $id): JsonResponse
    {
        return ApiResponse::data(new ReportTemplateResource(
            $this->templates->setStatus($this->templates->find($id), EntityStatus::Active)
        ));
    }

    public function deactivate(string $id): JsonResponse
    {
        return ApiResponse::data(new ReportTemplateResource(
            $this->templates->setStatus($this->templates->find($id), EntityStatus::Inactive)
        ));
    }
}
