<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentProviderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentProviderRequest;
use App\Http\Requests\UpdatePaymentProviderRequest;
use App\Http\Resources\PaymentProviderResource;
use App\Services\PaymentProviderService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/** PRD 13Q §32. */
class PaymentProviderController extends Controller
{
    public function __construct(private readonly PaymentProviderService $providers) {}

    public function index(): JsonResponse
    {
        return ApiResponse::data(PaymentProviderResource::collection($this->providers->list()));
    }

    public function store(StorePaymentProviderRequest $request): JsonResponse
    {
        return ApiResponse::data(new PaymentProviderResource($this->providers->create($request->validated())), status: 201);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::data(new PaymentProviderResource($this->providers->find($id)));
    }

    public function update(UpdatePaymentProviderRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new PaymentProviderResource(
            $this->providers->update($this->providers->find($id), $request->validated())
        ));
    }

    public function activate(string $id): JsonResponse
    {
        return $this->transition($id, PaymentProviderStatus::Active);
    }

    public function deactivate(string $id): JsonResponse
    {
        return $this->transition($id, PaymentProviderStatus::Inactive);
    }

    /** PRD 13T §40 — uji kesiapan tanpa menampilkan kredensial. */
    public function test(string $id): JsonResponse
    {
        return ApiResponse::data($this->providers->test($this->providers->find($id)));
    }

    private function transition(string $id, PaymentProviderStatus $status): JsonResponse
    {
        return ApiResponse::data(new PaymentProviderResource(
            $this->providers->changeStatus($this->providers->find($id), $status)
        ));
    }
}
