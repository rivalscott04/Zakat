<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\EvaluateProgramEligibilityRequest;
use App\Http\Requests\ListRequest;
use App\Http\Requests\OverrideProgramEligibilityRequest;
use App\Http\Requests\ReasonRequest;
use App\Http\Requests\StoreProgramActivityRequest;
use App\Http\Requests\StoreProgramBudgetRequest;
use App\Http\Requests\StoreProgramCategoryRequest;
use App\Http\Requests\StoreProgramCommitmentRequest;
use App\Http\Requests\StoreProgramEligibilityRuleRequest;
use App\Http\Requests\StoreProgramEnrollmentRequest;
use App\Http\Requests\StoreProgramFundRequest;
use App\Http\Requests\StoreProgramOutcomeRequest;
use App\Http\Requests\StoreProgramOutputRequest;
use App\Http\Requests\StoreProgramParticipantRequest;
use App\Http\Requests\StoreProgramPeriodRequest;
use App\Http\Requests\StoreProgramRequest;
use App\Http\Requests\StoreProgramTargetRequest;
use App\Http\Requests\UpdateProgramBudgetRequest;
use App\Http\Requests\UpdateProgramMetricRequest;
use App\Http\Requests\UpdateProgramRequest;
use App\Http\Resources\ProgramResource;
use App\Services\ProgramService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProgramController extends Controller
{
    public function __construct(private readonly ProgramService $programs) {}

    public function index(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(ProgramResource::collection($this->programs->list($request->filters())));
    }

    public function store(StoreProgramRequest $request): JsonResponse
    {
        return ApiResponse::data(new ProgramResource($this->programs->create($request->validated())), status: 201);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::data(new ProgramResource($this->programs->find($id)));
    }

    public function update(UpdateProgramRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new ProgramResource($this->programs->update($this->programs->find($id), $request->validated())));
    }

    public function transition(string $id, string $status): JsonResponse
    {
        return ApiResponse::data(new ProgramResource($this->programs->transition($this->programs->find($id), $status)));
    }

    public function categories(ListRequest $request): JsonResponse
    {
        return ApiResponse::data($this->programs->categories($request->filters()));
    }

    public function storeCategory(StoreProgramCategoryRequest $request): JsonResponse
    {
        return ApiResponse::data($this->programs->createCategory($request->validated()), status: 201);
    }

    public function budget(StoreProgramBudgetRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->budget($this->programs->find($id), $request->validated()), status: 201);
    }

    public function enroll(StoreProgramEnrollmentRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->enroll($this->programs->find($id), $request->validated()), status: 201);
    }

    public function approveEnrollment(string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->approveEnrollment($this->programs->enrollment($id)));
    }

    public function periods(string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->periods($this->programs->find($id)));
    }

    public function storePeriod(StoreProgramPeriodRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->createPeriod($this->programs->find($id), $request->validated()), status: 201);
    }

    public function funds(string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->find($id)->funds);
    }

    public function storeFund(StoreProgramFundRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->addFund($this->programs->find($id), $request->validated()), status: 201);
    }

    public function budgets(string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->budgets($this->programs->find($id)));
    }

    public function updateBudget(UpdateProgramBudgetRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->updateBudget($this->programs->budgetById($id), $request->validated()));
    }

    public function approveBudget(string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->approveBudget($this->programs->budgetById($id)));
    }

    public function rules(string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->rules($this->programs->find($id)));
    }

    public function storeRule(StoreProgramEligibilityRuleRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->createRule($this->programs->find($id), $request->validated()), status: 201);
    }

    public function evaluate(EvaluateProgramEligibilityRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->evaluate($this->programs->find($id), $request->validated()), status: 201);
    }

    public function eligible(string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->eligibleMustahiks($this->programs->find($id)));
    }

    public function overrideEligibility(OverrideProgramEligibilityRequest $request, string $evaluationId): JsonResponse
    {
        return ApiResponse::data($this->programs->overrideEligibility($this->programs->evaluation($evaluationId), $request->validated()));
    }

    public function enrollments(string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->enrollments($this->programs->find($id)));
    }

    public function rejectEnrollment(ReasonRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->rejectEnrollment($this->programs->enrollment($id), $request->validated('reason')));
    }

    public function withdrawEnrollment(ReasonRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->withdrawEnrollment($this->programs->enrollment($id), $request->validated('reason')));
    }

    public function waitlist(string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->find($id)->waitlists()->latest('position')->get());
    }

    public function addWaitlist(StoreProgramEnrollmentRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->waitlist($this->programs->find($id), $request->validated()), status: 201);
    }

    public function activities(string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->activities($this->programs->find($id)));
    }

    public function storeActivity(StoreProgramActivityRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->createActivity($this->programs->find($id), $request->validated()), status: 201);
    }

    public function updateActivity(StoreProgramActivityRequest $request, string $activityId): JsonResponse
    {
        return ApiResponse::data($this->programs->updateActivity($this->programs->activity($activityId), $request->validated()));
    }

    public function addParticipant(StoreProgramParticipantRequest $request, string $activityId): JsonResponse
    {
        return ApiResponse::data($this->programs->participant($this->programs->activity($activityId), $request->validated()), status: 201);
    }

    public function targets(string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->find($id)->targets);
    }

    public function storeTarget(StoreProgramTargetRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->target($this->programs->find($id), $request->validated()), status: 201);
    }

    public function updateTarget(UpdateProgramMetricRequest $request, string $targetId): JsonResponse
    {
        return ApiResponse::data($this->programs->updateTarget($this->programs->targetById($targetId), $request->validated()));
    }

    public function storeOutput(StoreProgramOutputRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->output($this->programs->find($id), $request->validated()), status: 201);
    }

    public function updateOutput(UpdateProgramMetricRequest $request, string $outputId): JsonResponse
    {
        return ApiResponse::data($this->programs->updateOutput($this->programs->outputById($outputId), $request->validated()));
    }

    public function storeOutcome(StoreProgramOutcomeRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->outcome($this->programs->find($id), $request->validated()), status: 201);
    }

    public function updateOutcome(UpdateProgramMetricRequest $request, string $outcomeId): JsonResponse
    {
        return ApiResponse::data($this->programs->updateOutcome($this->programs->outcomeById($outcomeId), $request->validated()));
    }

    public function commitment(StoreProgramCommitmentRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->commit($this->programs->find($id), $request->validated()), status: 201);
    }

    public function disburseCommitment(string $id): JsonResponse
    {
        return ApiResponse::data($this->programs->disburse($this->programs->commitmentById($id)));
    }

    public function dashboard(): JsonResponse
    {
        return ApiResponse::data($this->programs->dashboard());
    }
}
