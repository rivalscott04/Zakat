<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ZakatException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImportBankStatementRequest;
use App\Http\Requests\ListRequest;
use App\Http\Requests\MatchBankTransactionRequest;
use App\Http\Requests\ReasonRequest;
use App\Http\Requests\StoreBankAccountRequest;
use App\Http\Requests\StoreInternalTransactionRequest;
use App\Http\Requests\StoreReconciliationAdjustmentRequest;
use App\Http\Requests\StoreReconciliationSessionRequest;
use App\Http\Resources\BankAccountResource;
use App\Http\Resources\BankTransactionResource;
use App\Http\Resources\ReconciliationSessionResource;
use App\Models\BankTransaction;
use App\Models\ReconciliationAdjustment;
use App\Services\BankReconciliationService;
use App\Services\ReconciliationSyncService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** PRD 14W — API bank reconciliation. */
class BankReconciliationController extends Controller
{
    public function __construct(
        private readonly BankReconciliationService $service,
        private readonly ReconciliationSyncService $sync,
    ) {}

    // ------------------------------------------------------------- rekening

    public function accounts(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(BankAccountResource::collection($this->service->accounts($request->filters())));
    }

    public function storeAccount(StoreBankAccountRequest $request): JsonResponse
    {
        return ApiResponse::data(new BankAccountResource($this->service->createAccount($request->validated())), status: 201);
    }

    public function showAccount(string $id): JsonResponse
    {
        return ApiResponse::data(new BankAccountResource($this->service->account($id)));
    }

    // ---------------------------------------------------------------- import

    public function import(ImportBankStatementRequest $request): JsonResponse
    {
        $data = $request->validated();

        return ApiResponse::data(
            $this->service->import($this->service->account($data['bank_account_id']), $request->file('file'), $data),
            status: 201
        );
    }

    // -------------------------------------------------------------- matching

    public function transactions(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(BankTransactionResource::collection($this->service->transactions($request->filters())));
    }

    public function match(MatchBankTransactionRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new BankTransactionResource($this->service->match($this->transaction($id), $request->validated())));
    }

    public function exclude(ReasonRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new BankTransactionResource($this->service->exclude($this->transaction($id), $request->validated('reason'))));
    }

    // ------------------------------------------------------ transaksi internal

    /** PRD 14H — tarik transaksi internal dari modul payment, collection, dan distribution. */
    public function syncInternal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        return ApiResponse::data($this->sync->sync($data['from'], $data['to']));
    }

    public function storeInternal(StoreInternalTransactionRequest $request): JsonResponse
    {
        return ApiResponse::data($this->sync->createManual($request->validated()), status: 201);
    }

    // ------------------------------------------------------------------ sesi

    public function sessions(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(ReconciliationSessionResource::collection($this->service->sessions($request->filters())));
    }

    public function storeSession(StoreReconciliationSessionRequest $request): JsonResponse
    {
        return ApiResponse::data(new ReconciliationSessionResource($this->service->createSession($request->validated())), status: 201);
    }

    public function summary(string $id): JsonResponse
    {
        return ApiResponse::data($this->service->summary($this->service->session($id)));
    }

    public function autoMatch(string $id): JsonResponse
    {
        return ApiResponse::data(new ReconciliationSessionResource($this->service->autoMatch($this->service->session($id))));
    }

    public function complete(string $id): JsonResponse
    {
        return ApiResponse::data(new ReconciliationSessionResource($this->service->complete($this->service->session($id))));
    }

    public function close(string $id): JsonResponse
    {
        return ApiResponse::data(new ReconciliationSessionResource($this->service->close($this->service->session($id))));
    }

    // ------------------------------------------------------------ adjustment

    public function storeAdjustment(StoreReconciliationAdjustmentRequest $request): JsonResponse
    {
        return ApiResponse::data($this->service->createAdjustment($request->validated()), status: 201);
    }

    public function approveAdjustment(string $id): JsonResponse
    {
        return ApiResponse::data($this->service->approveAdjustment($this->adjustment($id)));
    }

    public function rejectAdjustment(ReasonRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->service->rejectAdjustment($this->adjustment($id), $request->validated('reason')));
    }

    private function transaction(string $id): BankTransaction
    {
        return BankTransaction::find($id) ?? throw ZakatException::notFound('Transaksi bank tidak ditemukan.');
    }

    private function adjustment(string $id): ReconciliationAdjustment
    {
        return ReconciliationAdjustment::find($id) ?? throw ZakatException::notFound('Adjustment tidak ditemukan.');
    }
}
