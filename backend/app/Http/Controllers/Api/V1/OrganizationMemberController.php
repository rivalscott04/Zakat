<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MembershipStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListRequest;
use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Http\Resources\OrganizationMemberResource;
use App\Services\MembershipService;
use App\Services\OrganizationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** PRD 02 §31 — membership endpoints. */
class OrganizationMemberController extends Controller
{
    public function __construct(
        private readonly MembershipService $members,
        private readonly OrganizationService $organizations,
    ) {}

    public function index(ListRequest $request, string $organizationId): JsonResponse
    {
        $organization = $this->organizations->findForUser($request->user(), $organizationId);

        return ApiResponse::data(OrganizationMemberResource::collection(
            $this->members->paginate($organization, $request->filters())
        ));
    }

    public function store(StoreMemberRequest $request, string $organizationId): JsonResponse
    {
        $organization = $this->organizations->findForUser($request->user(), $organizationId);

        $member = $this->members->add(
            $organization,
            $request->validated('user_id'),
            $request->validated('member_type'),
        );

        return ApiResponse::data(new OrganizationMemberResource($member), status: 201);
    }

    public function update(UpdateMemberRequest $request, string $organizationId, string $memberId): JsonResponse
    {
        $member = $this->resolve($request, $organizationId, $memberId);

        return ApiResponse::data(new OrganizationMemberResource(
            $this->members->update($member, $request->validated())
        ));
    }

    public function activate(Request $request, string $organizationId, string $memberId): JsonResponse
    {
        return $this->transition($request, $organizationId, $memberId, MembershipStatus::Active);
    }

    public function deactivate(Request $request, string $organizationId, string $memberId): JsonResponse
    {
        return $this->transition($request, $organizationId, $memberId, MembershipStatus::Inactive);
    }

    public function terminate(Request $request, string $organizationId, string $memberId): JsonResponse
    {
        return $this->transition($request, $organizationId, $memberId, MembershipStatus::Terminated);
    }

    private function transition(Request $request, string $organizationId, string $memberId, MembershipStatus $status): JsonResponse
    {
        $member = $this->resolve($request, $organizationId, $memberId);

        return ApiResponse::data(new OrganizationMemberResource(
            $this->members->changeStatus($member, $status)
        ));
    }

    private function resolve(Request $request, string $organizationId, string $memberId)
    {
        $organization = $this->organizations->findForUser($request->user(), $organizationId);

        return $this->members->findInOrganization($organization, $memberId);
    }
}
