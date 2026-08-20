<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrganizationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListRequest;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\SwitchOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\OrganizationSummaryResource;
use App\Services\OrganizationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** PRD 02 §29 dan §30 — organization endpoints. */
class OrganizationController extends Controller
{
    public function __construct(private readonly OrganizationService $organizations) {}

    public function index(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(OrganizationResource::collection(
            $this->organizations->paginate($request->user(), $request->filters())
        ));
    }

    /** PRD 02 §26 — daftar organisasi yang boleh dipilih user. */
    public function available(Request $request): JsonResponse
    {
        return ApiResponse::data(OrganizationSummaryResource::collection(
            $this->organizations->availableFor($request->user())
        ));
    }

    public function switch(SwitchOrganizationRequest $request): JsonResponse
    {
        $organization = $this->organizations->switchTo(
            $request,
            $request->user(),
            $request->validated('organization_id'),
        );

        return ApiResponse::data(new OrganizationSummaryResource($organization));
    }

    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $organization = $this->organizations->create(
            $request->user(),
            collect($data)->except('parent_id')->all(),
            $data['parent_id'] ?? null,
        );

        return ApiResponse::data(new OrganizationResource($organization), status: 201);
    }

    public function show(Request $request, string $organizationId): JsonResponse
    {
        return ApiResponse::data(new OrganizationResource(
            $this->organizations->findForUser($request->user(), $organizationId)
        ));
    }

    public function update(UpdateOrganizationRequest $request, string $organizationId): JsonResponse
    {
        $organization = $this->organizations->findForUser($request->user(), $organizationId);
        $data = $request->validated();

        return ApiResponse::data(new OrganizationResource($this->organizations->update(
            $organization,
            collect($data)->except('parent_id')->all(),
            array_key_exists('parent_id', $data),
            $data['parent_id'] ?? null,
        )));
    }

    public function children(Request $request, string $organizationId): JsonResponse
    {
        return ApiResponse::data(OrganizationSummaryResource::collection(
            $this->organizations->children($request->user(), $organizationId)
        ));
    }

    // PRD 02 §29 — status action eksplisit.

    public function activate(Request $request, string $organizationId): JsonResponse
    {
        return $this->transition($request, $organizationId, OrganizationStatus::Active);
    }

    public function deactivate(Request $request, string $organizationId): JsonResponse
    {
        return $this->transition($request, $organizationId, OrganizationStatus::Inactive);
    }

    public function suspend(Request $request, string $organizationId): JsonResponse
    {
        return $this->transition($request, $organizationId, OrganizationStatus::Suspended);
    }

    private function transition(Request $request, string $organizationId, OrganizationStatus $status): JsonResponse
    {
        $organization = $this->organizations->findForUser($request->user(), $organizationId);

        return ApiResponse::data(new OrganizationResource(
            $this->organizations->changeStatus($organization, $status)
        ));
    }
}
