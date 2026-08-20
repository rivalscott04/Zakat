<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AmilStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListRequest;
use App\Http\Requests\StoreAmilAssignmentRequest;
use App\Http\Requests\StoreAmilRequest;
use App\Http\Requests\UpdateAmilRequest;
use App\Http\Resources\AmilAssignmentResource;
use App\Http\Resources\AmilResource;
use App\Services\AmilService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/** PRD 02 §32 dan §33 — amil endpoints. */
class AmilController extends Controller
{
    public function __construct(private readonly AmilService $amils) {}

    public function index(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(AmilResource::collection($this->amils->paginate($request->filters())));
    }

    public function store(StoreAmilRequest $request): JsonResponse
    {
        return ApiResponse::data(new AmilResource($this->amils->create($request->validated())), status: 201);
    }

    public function show(string $amilId): JsonResponse
    {
        return ApiResponse::data(new AmilResource($this->amils->findInContext($amilId)));
    }

    public function update(UpdateAmilRequest $request, string $amilId): JsonResponse
    {
        $amil = $this->amils->findInContext($amilId);
        $data = $request->validated();

        return ApiResponse::data(new AmilResource($this->amils->update(
            $amil,
            collect($data)->except('user_id')->all(),
            array_key_exists('user_id', $data),
            $data['user_id'] ?? null,
        )));
    }

    public function activate(string $amilId): JsonResponse
    {
        return $this->transition($amilId, AmilStatus::Active);
    }

    public function deactivate(string $amilId): JsonResponse
    {
        return $this->transition($amilId, AmilStatus::Inactive);
    }

    public function suspend(string $amilId): JsonResponse
    {
        return $this->transition($amilId, AmilStatus::Suspended);
    }

    public function end(string $amilId): JsonResponse
    {
        return $this->transition($amilId, AmilStatus::Ended);
    }

    // --------------------------------------------------------- assignments

    public function assignments(string $amilId): JsonResponse
    {
        $amil = $this->amils->findInContext($amilId);

        return ApiResponse::data(AmilAssignmentResource::collection($amil->assignments));
    }

    public function storeAssignment(StoreAmilAssignmentRequest $request, string $amilId): JsonResponse
    {
        $amil = $this->amils->findInContext($amilId);

        return ApiResponse::data(
            new AmilAssignmentResource($this->amils->assign($amil, $request->validated())),
            status: 201
        );
    }

    public function endAssignment(string $assignmentId): JsonResponse
    {
        return ApiResponse::data(new AmilAssignmentResource(
            $this->amils->endAssignment($this->amils->findAssignment($assignmentId))
        ));
    }

    private function transition(string $amilId, AmilStatus $status): JsonResponse
    {
        $amil = $this->amils->findInContext($amilId);

        return ApiResponse::data(new AmilResource($this->amils->changeStatus($amil, $status)));
    }
}
