<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListRequest;
use App\Http\Requests\ReasonRequest;
use App\Http\Requests\StorePaymentRefundRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\VerifyPaymentRequest;
use App\Http\Resources\PaymentRefundResource;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentRefundService;
use App\Services\PaymentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/** PRD 13Q §33 dan §34. */
class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly PaymentRefundService $refunds,
    ) {}

    public function index(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(PaymentResource::collection($this->payments->list($request->filters())));
    }

    public function summary(): JsonResponse
    {
        return ApiResponse::data(['by_status' => $this->payments->summary()]);
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        return ApiResponse::data(new PaymentResource($this->payments->create($request->validated())), status: 201);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::data(new PaymentResource($this->payments->find($id)));
    }

    public function verify(VerifyPaymentRequest $request, string $id): JsonResponse
    {
        $this->payments->verifyManually($this->payments->find($id), $request->validated('reason'));

        return ApiResponse::data(new PaymentResource($this->payments->find($id)));
    }

    public function cancel(ReasonRequest $request, string $id): JsonResponse
    {
        $this->payments->cancel($this->payments->find($id), $request->validated('reason'));

        return ApiResponse::data(new PaymentResource($this->payments->find($id)));
    }

    public function refreshStatus(string $id): JsonResponse
    {
        $this->payments->refreshStatus($this->payments->find($id));

        return ApiResponse::data(new PaymentResource($this->payments->find($id)));
    }

    public function reconcile(string $id): JsonResponse
    {
        $this->payments->reconcile($this->payments->find($id));

        return ApiResponse::data(new PaymentResource($this->payments->find($id)));
    }

    // ------------------------------------------------------------------ refund

    public function refunds(string $id): JsonResponse
    {
        return ApiResponse::data(PaymentRefundResource::collection($this->payments->find($id)->refunds));
    }

    public function storeRefund(StorePaymentRefundRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(
            new PaymentRefundResource($this->refunds->request($this->payments->find($id), $request->validated())),
            status: 201
        );
    }

    public function approveRefund(string $refundId): JsonResponse
    {
        return ApiResponse::data(new PaymentRefundResource($this->refunds->approve($this->refunds->find($refundId))));
    }

    public function rejectRefund(ReasonRequest $request, string $refundId): JsonResponse
    {
        return ApiResponse::data(new PaymentRefundResource(
            $this->refunds->reject($this->refunds->find($refundId), $request->validated('reason'))
        ));
    }
}
