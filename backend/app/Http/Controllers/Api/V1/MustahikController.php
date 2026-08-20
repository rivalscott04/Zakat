<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignMustahikAsnafRequest;
use App\Http\Requests\CheckMustahikDuplicateRequest;
use App\Http\Requests\ListRequest;
use App\Http\Requests\StoreMustahikAddressRequest;
use App\Http\Requests\StoreMustahikIdentityRequest;
use App\Http\Requests\StoreMustahikRequest;
use App\Http\Resources\MustahikResource;
use App\Services\MustahikService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MustahikController extends Controller
{
    public function __construct(private readonly MustahikService $mustahiks) {}

    public function index(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(MustahikResource::collection($this->mustahiks->list($request->filters() + $request->only(['verification_status']))));
    }

    public function store(StoreMustahikRequest $request): JsonResponse
    {
        return ApiResponse::data(new MustahikResource($this->mustahiks->create($request->validated())), status: 201);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::data(new MustahikResource($this->mustahiks->find($id)));
    }

    public function update(StoreMustahikRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new MustahikResource($this->mustahiks->update($this->mustahiks->find($id), $request->validated())));
    }

    public function duplicate(CheckMustahikDuplicateRequest $request): JsonResponse
    {
        return ApiResponse::data($this->mustahiks->duplicateCheck($request->validated()));
    }

    public function identity(StoreMustahikIdentityRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->mustahiks->identity($this->mustahiks->find($id), $request->validated()), status: 201);
    }

    public function address(StoreMustahikAddressRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->mustahiks->address($this->mustahiks->find($id), $request->validated()), status: 201);
    }

    public function asnaf(AssignMustahikAsnafRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->mustahiks->asnaf($this->mustahiks->find($id), $request->validated()), status: 201);
    }

    public function verify(Request $request, string $id): JsonResponse
    {
        return ApiResponse::data(new MustahikResource($this->mustahiks->verify($this->mustahiks->find($id), $request->validate(['status' => ['required', 'in:verified,invalid,pending']])['status'])));
    }
}
