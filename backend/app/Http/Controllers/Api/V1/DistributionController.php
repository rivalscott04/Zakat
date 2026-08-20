<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteDistributionRequest;
use App\Http\Requests\ListRequest;
use App\Http\Requests\ReasonRequest;
use App\Http\Requests\StoreDistributionRequest;
use App\Http\Requests\UpdateDistributionRequest;
use App\Http\Resources\DistributionRequestResource;
use App\Http\Resources\DistributionResource;
use App\Models\DistributionRequest as DistributionRequestModel;
use App\Services\DistributionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DistributionController extends Controller
{
    public function __construct(private readonly DistributionService $distributions) {}

    public function index(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(DistributionResource::collection($this->distributions->list($request->filters())));
    }

    public function store(StoreDistributionRequest $request): JsonResponse
    {
        return ApiResponse::data(new DistributionResource($this->distributions->create($request->validated())), status: 201);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::data(new DistributionResource($this->distributions->find($id)));
    }

    public function update(UpdateDistributionRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new DistributionResource($this->distributions->update($this->distributions->find($id), $request->validated())));
    }

    public function action(string $action, string $id): JsonResponse
    {
        return ApiResponse::data(new DistributionResource($this->distributions->{$action}($this->distributions->find($id))));
    }

    public function complete(CompleteDistributionRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new DistributionResource($this->distributions->complete($this->distributions->find($id), $request->validated())));
    }

    public function cancel(ReasonRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new DistributionResource($this->distributions->cancel($this->distributions->find($id), $request->validated('reason'))));
    }

    public function reverse(ReasonRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new DistributionResource($this->distributions->reverse($this->distributions->find($id), $request->validated('reason'))));
    }

    public function requests(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(DistributionRequestResource::collection(DistributionRequestModel::with(['mustahik', 'fund'])->latest()->paginate(15)));
    }

    public function storeRequest(StoreDistributionRequest $request): JsonResponse
    {
        return ApiResponse::data(new DistributionRequestResource($this->distributions->createRequest($request->validated())), status: 201);
    }

    public function approveRequest(string $id): JsonResponse
    {
        $request = DistributionRequestModel::findOrFail($id);

        return ApiResponse::data(new DistributionRequestResource($this->distributions->approveRequest($request)));
    }
}
