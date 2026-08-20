<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignAssessmentRequest;
use App\Http\Requests\CancelAssessmentRequest;
use App\Http\Requests\ListRequest;
use App\Http\Requests\ReassessAssessmentRequest;
use App\Http\Requests\ReviewAssessmentRequest;
use App\Http\Requests\StoreAssessmentRequest;
use App\Http\Requests\StoreAssessmentRequestInstance;
use App\Http\Requests\StoreAssessmentTemplateRequest;
use App\Http\Requests\UpdateAssessmentRequest;
use App\Http\Resources\AssessmentRequestResource;
use App\Http\Resources\AssessmentResource;
use App\Http\Resources\AssessmentTemplateResource;
use App\Models\AssessmentTemplate;
use App\Services\AssessmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class AssessmentController extends Controller
{
    public function __construct(private readonly AssessmentService $assessments) {}

    public function requests(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(AssessmentRequestResource::collection($this->assessments->requestList($request->filters() + $request->only('priority'))));
    }

    public function storeRequest(StoreAssessmentRequest $request): JsonResponse
    {
        return ApiResponse::data(new AssessmentRequestResource($this->assessments->createRequest($request->validated())), status: 201);
    }

    public function showRequest(string $id): JsonResponse
    {
        return ApiResponse::data(new AssessmentRequestResource($this->assessments->request($id)));
    }

    public function assign(AssignAssessmentRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new AssessmentRequestResource($this->assessments->assign($this->assessments->request($id), $request->validated())));
    }

    public function cancel(CancelAssessmentRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new AssessmentRequestResource($this->assessments->cancelRequest($this->assessments->request($id), $request->validated('reason'))));
    }

    public function templates(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(AssessmentTemplateResource::collection($this->assessments->templateList($request->filters())));
    }

    public function storeTemplate(StoreAssessmentTemplateRequest $request): JsonResponse
    {
        return ApiResponse::data(new AssessmentTemplateResource($this->assessments->createTemplate($request->validated())), status: 201);
    }

    public function publishTemplate(string $id): JsonResponse
    {
        $template = AssessmentTemplate::find($id) ?? abort(404);

        return ApiResponse::data(new AssessmentTemplateResource($this->assessments->publishTemplate($template)));
    }

    public function index(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(AssessmentResource::collection($this->assessments->list($request->filters() + $request->only('mustahik_id'))));
    }

    public function store(StoreAssessmentRequestInstance $request): JsonResponse
    {
        return ApiResponse::data(new AssessmentResource($this->assessments->create($request->validated())), status: 201);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::data(new AssessmentResource($this->assessments->find($id)));
    }

    public function update(UpdateAssessmentRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new AssessmentResource($this->assessments->update($this->assessments->find($id), $request->validated())));
    }

    public function submit(string $id): JsonResponse
    {
        return ApiResponse::data(new AssessmentResource($this->assessments->submit($this->assessments->find($id))));
    }

    public function review(ReviewAssessmentRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new AssessmentResource($this->assessments->review($this->assessments->find($id), $request->validated())));
    }

    public function reassess(ReassessAssessmentRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new AssessmentResource($this->assessments->reassess($this->assessments->find($id), $request->validated())), status: 201);
    }
}
