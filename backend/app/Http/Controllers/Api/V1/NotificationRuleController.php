<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListRequest;
use App\Http\Requests\StoreNotificationRuleRequest;
use App\Http\Resources\NotificationRuleResource;
use App\Models\NotificationRule;
use App\Services\NotificationRuleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/** PRD 16U §45. */
class NotificationRuleController extends Controller
{
    public function __construct(private readonly NotificationRuleService $rules) {}

    public function index(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(NotificationRuleResource::collection($this->rules->list($request->validated())));
    }

    public function store(StoreNotificationRuleRequest $request): JsonResponse
    {
        return ApiResponse::data(new NotificationRuleResource($this->rules->create($request->validated())), status: 201);
    }

    public function update(StoreNotificationRuleRequest $request, string $id): JsonResponse
    {
        $rule = NotificationRule::query()->findOrFail($id);

        return ApiResponse::data(new NotificationRuleResource($this->rules->update($rule, $request->validated())));
    }

    public function enable(string $id): JsonResponse
    {
        return $this->toggle($id, true);
    }

    public function disable(string $id): JsonResponse
    {
        return $this->toggle($id, false);
    }

    private function toggle(string $id, bool $enabled): JsonResponse
    {
        $rule = NotificationRule::query()->findOrFail($id);

        return ApiResponse::data(new NotificationRuleResource($this->rules->setEnabled($rule, $enabled)));
    }
}
