<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListRequest;
use App\Http\Requests\ReverseJournalRequest;
use App\Http\Requests\StoreAccountingAccountRequest;
use App\Http\Requests\StoreAccountingEventRequest;
use App\Http\Requests\StoreAccountingPeriodRequest;
use App\Http\Requests\StoreJournalRequest;
use App\Http\Resources\AccountResource;
use App\Http\Resources\JournalResource;
use App\Http\Resources\LedgerLineResource;
use App\Models\AccountingPeriod;
use App\Services\AccountingService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function __construct(private readonly AccountingService $accounting) {}

    public function accounts(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(AccountResource::collection($this->accounting->accounts($request->filters() + $request->only(['account_type']))));
    }

    public function createAccount(StoreAccountingAccountRequest $request): JsonResponse
    {
        return ApiResponse::data(new AccountResource($this->accounting->createAccount($request->validated())), status: 201);
    }

    public function periods(): JsonResponse
    {
        return ApiResponse::data($this->accounting->periods());
    }

    public function createPeriod(StoreAccountingPeriodRequest $request): JsonResponse
    {
        return ApiResponse::data($this->accounting->createPeriod($request->validated()), status: 201);
    }

    public function lockPeriod(string $id): JsonResponse
    {
        return ApiResponse::data($this->accounting->lockPeriod(AccountingPeriod::findOrFail($id)));
    }

    public function closePeriod(string $id): JsonResponse
    {
        return ApiResponse::data($this->accounting->closePeriod(AccountingPeriod::findOrFail($id)));
    }

    public function journals(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(JournalResource::collection($this->accounting->journals($request->filters())));
    }

    public function createJournal(StoreJournalRequest $request): JsonResponse
    {
        return ApiResponse::data(new JournalResource($this->accounting->createJournal($request->validated())), status: 201);
    }

    public function showJournal(string $id): JsonResponse
    {
        return ApiResponse::data(new JournalResource($this->accounting->findJournal($id)));
    }

    public function submit(string $id): JsonResponse
    {
        return ApiResponse::data(new JournalResource($this->accounting->submit($this->accounting->findJournal($id))));
    }

    public function approve(string $id): JsonResponse
    {
        return ApiResponse::data(new JournalResource($this->accounting->approve($this->accounting->findJournal($id))));
    }

    public function post(string $id): JsonResponse
    {
        return ApiResponse::data(new JournalResource($this->accounting->post($this->accounting->findJournal($id))));
    }

    public function reverse(ReverseJournalRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new JournalResource($this->accounting->reverse($this->accounting->findJournal($id), $request->validated())), status: 201);
    }

    public function ledger(Request $request): JsonResponse
    {
        return ApiResponse::data(LedgerLineResource::collection(
            $this->accounting->ledger($request->only(['account_id', 'date_from', 'date_to', 'per_page', 'page']))
        ));
    }

    public function trialBalance(Request $request): JsonResponse
    {
        return ApiResponse::data($this->accounting->trialBalance($request->only(['accounting_period_id'])));
    }

    public function event(StoreAccountingEventRequest $request): JsonResponse
    {
        return ApiResponse::data($this->accounting->event($request->validated()), status: 201);
    }
}
