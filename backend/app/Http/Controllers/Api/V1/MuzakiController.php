<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MuzakiStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListRequest;
use App\Http\Requests\StoreMuzakiRequest;
use App\Http\Requests\UpdateMuzakiRequest;
use App\Http\Resources\MuzakiResource;
use App\Services\MuzakiService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class MuzakiController extends Controller
{
    public function __construct(private readonly MuzakiService $muzakis) {}

    public function index(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(MuzakiResource::collection($this->muzakis->paginate($request->filters())));
    }

    public function store(StoreMuzakiRequest $request): JsonResponse
    {
        return ApiResponse::data(new MuzakiResource($this->muzakis->create($request->validated())), status: 201);
    }

    public function show(string $muzakiId): JsonResponse
    {
        return ApiResponse::data(new MuzakiResource($this->muzakis->findInContext($muzakiId)));
    }

    public function update(UpdateMuzakiRequest $request, string $muzakiId): JsonResponse
    {
        return ApiResponse::data(new MuzakiResource($this->muzakis->update($this->muzakis->findInContext($muzakiId), $request->validated())));
    }

    public function activate(string $id): JsonResponse
    {
        return $this->transition($id, MuzakiStatus::Active);
    }

    public function deactivate(string $id): JsonResponse
    {
        return $this->transition($id, MuzakiStatus::Inactive);
    }

    public function archive(string $id): JsonResponse
    {
        return $this->transition($id, MuzakiStatus::Archived);
    }

    public function summary(string $id): JsonResponse
    {
        $m = $this->muzakis->findInContext($id);

        return ApiResponse::data($this->muzakis->summary($m));
    }

    private function transition(string $id, MuzakiStatus $status): JsonResponse
    {
        return ApiResponse::data(new MuzakiResource($this->muzakis->changeStatus($this->muzakis->findInContext($id), $status)));
    }
}
