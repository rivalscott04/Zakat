<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListRequest;
use App\Http\Requests\ReasonRequest;
use App\Http\Requests\StoreBatchBeneficiaryRequest;
use App\Http\Requests\StoreDistributionBatchRequest;
use App\Http\Resources\DistributionBatchResource;
use App\Http\Resources\DistributionBeneficiaryResource;
use App\Services\DistributionBatchService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/** PRD 12AA §63 — batch distribution. */
class DistributionBatchController extends Controller
{
    public function __construct(private readonly DistributionBatchService $batches) {}

    public function index(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(DistributionBatchResource::collection($this->batches->list($request->filters())));
    }

    public function store(StoreDistributionBatchRequest $request): JsonResponse
    {
        return ApiResponse::data(new DistributionBatchResource($this->batches->create($request->validated())), status: 201);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::data(new DistributionBatchResource($this->batches->find($id)));
    }

    public function storeBeneficiary(StoreBatchBeneficiaryRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(
            new DistributionBeneficiaryResource($this->batches->addBeneficiary($this->batches->find($id), $request->validated())),
            status: 201
        );
    }

    public function destroyBeneficiary(string $id, string $beneficiaryId): JsonResponse
    {
        $this->batches->removeBeneficiary($this->batches->find($id), $beneficiaryId);

        return ApiResponse::data(new DistributionBatchResource($this->batches->find($id)));
    }

    public function validateBatch(string $id): JsonResponse
    {
        return $this->respond($this->batches->validateBatch($this->batches->find($id))->id);
    }

    public function submit(string $id): JsonResponse
    {
        return $this->respond($this->batches->submit($this->batches->find($id))->id);
    }

    public function approve(string $id): JsonResponse
    {
        return $this->respond($this->batches->approve($this->batches->find($id))->id);
    }

    public function process(string $id): JsonResponse
    {
        return $this->respond($this->batches->process($this->batches->find($id))->id);
    }

    public function cancel(ReasonRequest $request, string $id): JsonResponse
    {
        return $this->respond($this->batches->cancel($this->batches->find($id), $request->validated('reason'))->id);
    }

    private function respond(string $id): JsonResponse
    {
        return ApiResponse::data(new DistributionBatchResource($this->batches->find($id)));
    }
}
