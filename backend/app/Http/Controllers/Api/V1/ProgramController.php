<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListRequest;
use App\Http\Requests\StoreProgramBudgetRequest;
use App\Http\Requests\StoreProgramCategoryRequest;
use App\Http\Requests\StoreProgramEnrollmentRequest;
use App\Http\Requests\StoreProgramRequest;
use App\Http\Requests\UpdateProgramRequest;
use App\Http\Resources\ProgramResource;
use App\Models\ProgramEnrollment;
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
        return ApiResponse::data($this->programs->approveEnrollment(ProgramEnrollment::findOrFail($id)));
    }
}
