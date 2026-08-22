<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListRequest;
use App\Http\Requests\StoreNotificationBatchRequest;
use App\Http\Resources\NotificationBatchResource;
use App\Models\NotificationBatch;
use App\Services\NotificationBatchService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/** PRD 16U §47. */
class NotificationBatchController extends Controller
{
    public function __construct(private readonly NotificationBatchService $batches) {}

    public function index(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(NotificationBatchResource::collection($this->batches->list($request->validated())));
    }

    public function store(StoreNotificationBatchRequest $request): JsonResponse
    {
        return ApiResponse::data(new NotificationBatchResource($this->batches->create($request->validated())), status: 201);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::data(new NotificationBatchResource($this->batches->find($id)));
    }

    public function send(string $id): JsonResponse
    {
        return ApiResponse::data(new NotificationBatchResource($this->batches->send(NotificationBatch::query()->findOrFail($id))));
    }

    public function cancel(string $id): JsonResponse
    {
        return ApiResponse::data(new NotificationBatchResource($this->batches->cancel(NotificationBatch::query()->findOrFail($id))));
    }
}
