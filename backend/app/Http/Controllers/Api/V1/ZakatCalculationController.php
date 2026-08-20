<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustZakatCalculationRequest;
use App\Http\Requests\CancelZakatCalculationRequest;
use App\Http\Requests\ListRequest;
use App\Http\Requests\RecalculateZakatCalculationRequest;
use App\Http\Requests\StoreZakatCalculationRequest;
use App\Http\Resources\CollectionResource;
use App\Http\Resources\ZakatCalculationResource;
use App\Services\CollectionService;
use App\Services\ZakatCalculationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ZakatCalculationController extends Controller
{
    public function __construct(private readonly ZakatCalculationService $calculations, private readonly CollectionService $collections) {}

    public function index(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(ZakatCalculationResource::collection($this->calculations->list($request->filters())));
    }

    public function store(StoreZakatCalculationRequest $request): JsonResponse
    {
        return ApiResponse::data(new ZakatCalculationResource($this->calculations->create($request->validated())), status: 201);
    }

    public function preview(StoreZakatCalculationRequest $request): JsonResponse
    {
        return ApiResponse::data($this->calculations->preview($request->validated()));
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::data(new ZakatCalculationResource($this->calculations->find($id)));
    }

    public function calculate(string $id): JsonResponse
    {
        return ApiResponse::data(new ZakatCalculationResource($this->calculations->calculate($this->calculations->find($id))));
    }

    public function confirm(string $id): JsonResponse
    {
        return ApiResponse::data(new ZakatCalculationResource($this->calculations->confirm($this->calculations->find($id))));
    }

    public function cancel(CancelZakatCalculationRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new ZakatCalculationResource($this->calculations->cancel($this->calculations->find($id), $request->validated('reason'))));
    }

    public function recalculate(RecalculateZakatCalculationRequest $request, string $id): JsonResponse
    {
        $old = $this->calculations->find($id);

        return ApiResponse::data(new ZakatCalculationResource($this->calculations->recalculate($old, $request->validated())), status: 201);
    }

    public function adjust(AdjustZakatCalculationRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new ZakatCalculationResource($this->calculations->adjust($this->calculations->find($id), $request->validated())));
    }

    public function convert(string $id): JsonResponse
    {
        return ApiResponse::data(new CollectionResource($this->collections->fromCalculation(['calculation_id' => $id])), status: 201);
    }
}
