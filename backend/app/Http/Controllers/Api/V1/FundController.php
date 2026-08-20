<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FundAdjustmentRequest;
use App\Http\Requests\FundCollectionInflowRequest;
use App\Http\Requests\FundOperationRequest;
use App\Http\Requests\FundReconciliationRequest;
use App\Http\Requests\ListRequest;
use App\Http\Requests\ReleaseFundReservationRequest;
use App\Http\Requests\StoreFundRequest;
use App\Http\Requests\StoreFundTransferRequest;
use App\Http\Resources\FundResource;
use App\Models\FundAllocation;
use App\Models\FundReservation;
use App\Models\FundTransfer;
use App\Services\FundService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class FundController extends Controller
{
    public function __construct(private readonly FundService $funds) {}

    public function index(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(FundResource::collection($this->funds->list($request->filters() + $request->only(['fund_type']))));
    }

    public function store(StoreFundRequest $request): JsonResponse
    {
        return ApiResponse::data(new FundResource($this->funds->create($request->validated())), status: 201);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::data(new FundResource($this->funds->find($id)));
    }

    public function balance(string $id): JsonResponse
    {
        $fund = $this->funds->find($id);

        return ApiResponse::data($this->funds->balance($fund));
    }

    public function movements(ListRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->funds->movements($this->funds->find($id), $request->filters() + $request->only(['movement_type', 'direction'])));
    }

    public function inflow(FundOperationRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->funds->inflow($this->funds->find($id), $request->validated()), status: 201);
    }

    public function inflowFromCollection(FundCollectionInflowRequest $request): JsonResponse
    {
        return ApiResponse::data($this->funds->inflowFromCollection($request->validated()), status: 201);
    }

    public function outflow(FundOperationRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->funds->outflow($this->funds->find($id), $request->validated()), status: 201);
    }

    public function allocation(FundOperationRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->funds->allocation($this->funds->find($id), $request->validated()), status: 201);
    }

    public function approveAllocation(string $id): JsonResponse
    {
        return ApiResponse::data($this->funds->approveAllocation(FundAllocation::findOrFail($id)));
    }

    public function reservation(FundOperationRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->funds->reservation($this->funds->find($id), $request->validated()), status: 201);
    }

    public function releaseReservation(ReleaseFundReservationRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->funds->releaseReservation(FundReservation::findOrFail($id), $request->validated('reason')));
    }

    public function transfer(StoreFundTransferRequest $request): JsonResponse
    {
        return ApiResponse::data($this->funds->transfer($request->validated()), status: 201);
    }

    public function approveTransfer(string $id): JsonResponse
    {
        return ApiResponse::data($this->funds->approveTransfer(FundTransfer::findOrFail($id)));
    }

    public function availability(FundOperationRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->funds->availability($this->funds->find($id), (string) $request->validated('amount')));
    }

    public function reconcile(FundReconciliationRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->funds->reconcile($this->funds->find($id), $request->validated()), status: 201);
    }

    public function adjustment(FundAdjustmentRequest $request): JsonResponse
    {
        return ApiResponse::data($this->funds->adjust($request->validated()), status: 201);
    }
}
