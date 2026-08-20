<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelCollectionRequest;
use App\Http\Requests\ListRequest;
use App\Http\Requests\StoreCollectionFromCalculationRequest;
use App\Http\Requests\StoreCollectionPaymentRequest;
use App\Http\Requests\StoreCollectionRequest;
use App\Http\Requests\VerifyCollectionPaymentRequest;
use App\Http\Resources\CollectionResource;
use App\Models\CollectionPayment;
use App\Services\CollectionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CollectionController extends Controller
{
    public function __construct(private readonly CollectionService $collections) {}

    public function index(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(CollectionResource::collection($this->collections->list($request->filters())));
    }

    public function store(StoreCollectionRequest $request): JsonResponse
    {
        return ApiResponse::data(new CollectionResource($this->collections->create($request->validated())), status: 201);
    }

    public function fromCalculation(StoreCollectionFromCalculationRequest $request): JsonResponse
    {
        return ApiResponse::data(new CollectionResource($this->collections->fromCalculation($request->validated())), status: 201);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::data(new CollectionResource($this->collections->find($id)));
    }

    public function confirm(string $id): JsonResponse
    {
        return ApiResponse::data(new CollectionResource($this->collections->confirm($this->collections->find($id))));
    }

    public function cancel(CancelCollectionRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new CollectionResource($this->collections->cancel($this->collections->find($id), $request->validated('reason'))));
    }

    public function reactivate(string $id): JsonResponse
    {
        return ApiResponse::data(new CollectionResource($this->collections->reactivate($this->collections->find($id))));
    }

    public function payment(StoreCollectionPaymentRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->collections->payment($this->collections->find($id), $request->validated()), status: 201);
    }

    public function verifyPayment(VerifyCollectionPaymentRequest $request, string $paymentId): JsonResponse
    {
        return ApiResponse::data(new CollectionResource($this->collections->verifyPayment(CollectionPayment::findOrFail($paymentId), $request->validated('status'))));
    }

    public function summary(): JsonResponse
    {
        return ApiResponse::data($this->collections->summary());
    }
}
