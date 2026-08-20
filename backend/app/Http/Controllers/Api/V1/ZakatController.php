<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ZakatStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListRequest;
use App\Http\Requests\StoreZakatCategoryRequest;
use App\Http\Requests\StoreZakatRuleRequest;
use App\Http\Requests\StoreZakatTypeRequest;
use App\Http\Resources\ZakatCategoryResource;
use App\Http\Resources\ZakatRuleResource;
use App\Http\Resources\ZakatTypeResource;
use App\Models\ZakatRule;
use App\Services\ZakatService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ZakatController extends Controller
{
    public function __construct(private readonly ZakatService $zakat) {}

    public function categories(ListRequest $r): JsonResponse
    {
        return ApiResponse::data(ZakatCategoryResource::collection($this->zakat->categories($r->filters())));
    }

    public function storeCategory(StoreZakatCategoryRequest $r): JsonResponse
    {
        return ApiResponse::data(new ZakatCategoryResource($this->zakat->createCategory($r->validated())), status: 201);
    }

    public function types(ListRequest $r): JsonResponse
    {
        return ApiResponse::data(ZakatTypeResource::collection($this->zakat->types($r->filters())));
    }

    public function storeType(StoreZakatTypeRequest $r): JsonResponse
    {
        return ApiResponse::data(new ZakatTypeResource($this->zakat->createType($r->validated())), status: 201);
    }

    public function rules(ListRequest $r): JsonResponse
    {
        return ApiResponse::data(ZakatRuleResource::collection($this->zakat->rules($r->filters())));
    }

    public function storeRule(StoreZakatRuleRequest $r): JsonResponse
    {
        return ApiResponse::data(new ZakatRuleResource($this->zakat->createRule($r->validated())), status: 201);
    }

    public function showRule(string $id): JsonResponse
    {
        return ApiResponse::data(new ZakatRuleResource(ZakatRule::with(['type', 'rates', 'nisab', 'haul'])->findOrFail($id)));
    }

    public function activate(string $id): JsonResponse
    {
        return $this->transition($id, ZakatStatus::Active);
    }

    public function expire(string $id): JsonResponse
    {
        return $this->transition($id, ZakatStatus::Expired);
    }

    public function archive(string $id): JsonResponse
    {
        return $this->transition($id, ZakatStatus::Archived);
    }

    private function transition(string $id, ZakatStatus $s): JsonResponse
    {
        return ApiResponse::data(new ZakatRuleResource($this->zakat->changeRuleStatus(ZakatRule::findOrFail($id), $s)));
    }
}
