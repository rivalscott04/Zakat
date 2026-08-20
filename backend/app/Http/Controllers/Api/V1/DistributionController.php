<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DistributionStatus;
use App\Exceptions\ZakatException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteDistributionRequest;
use App\Http\Requests\ConfirmDistributionRequest;
use App\Http\Requests\FailDistributionRequest;
use App\Http\Requests\ListRequest;
use App\Http\Requests\ReasonRequest;
use App\Http\Requests\ScheduleDistributionRequest;
use App\Http\Requests\StoreDistributionProofRequest;
use App\Http\Requests\StoreDistributionRequest;
use App\Http\Requests\StoreDistributionRequestRequest;
use App\Http\Requests\UpdateDistributionRequest;
use App\Http\Resources\DistributionProofResource;
use App\Http\Resources\DistributionRequestResource;
use App\Http\Resources\DistributionResource;
use App\Models\Distribution;
use App\Models\DistributionProof;
use App\Services\DistributionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/** PRD 12AA §61, §62, dan §64. */
class DistributionController extends Controller
{
    public function __construct(private readonly DistributionService $distributions) {}

    public function index(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(DistributionResource::collection($this->distributions->list($request->filters())));
    }

    public function store(StoreDistributionRequest $request): JsonResponse
    {
        return ApiResponse::data(new DistributionResource($this->distributions->create($request->validated())), status: 201);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::data(new DistributionResource($this->distributions->find($id)));
    }

    public function update(UpdateDistributionRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new DistributionResource(
            $this->distributions->update($this->distributions->find($id), $request->validated())
        ));
    }

    // --------------------------------------------------------------- lifecycle

    public function submit(string $id): JsonResponse
    {
        return $this->respond($this->distributions->submit($this->distributions->find($id)));
    }

    public function approve(string $id): JsonResponse
    {
        return $this->respond($this->distributions->approve($this->distributions->find($id)));
    }

    public function reject(ReasonRequest $request, string $id): JsonResponse
    {
        return $this->respond($this->distributions->reject($this->distributions->find($id), $request->validated('reason')));
    }

    public function reserve(string $id): JsonResponse
    {
        return $this->respond($this->distributions->reserve($this->distributions->find($id)));
    }

    public function schedule(ScheduleDistributionRequest $request, string $id): JsonResponse
    {
        return $this->respond($this->distributions->schedule($this->distributions->find($id), $request->validated()));
    }

    public function process(string $id): JsonResponse
    {
        return $this->respond($this->distributions->process($this->distributions->find($id)));
    }

    public function complete(CompleteDistributionRequest $request, string $id): JsonResponse
    {
        return $this->respond($this->distributions->complete($this->distributions->find($id), $request->validated()));
    }

    public function fail(FailDistributionRequest $request, string $id): JsonResponse
    {
        return $this->respond($this->distributions->fail($this->distributions->find($id), $request->validated()));
    }

    public function cancel(ReasonRequest $request, string $id): JsonResponse
    {
        return $this->respond($this->distributions->cancel($this->distributions->find($id), $request->validated('reason')));
    }

    public function reverse(ReasonRequest $request, string $id): JsonResponse
    {
        return $this->respond($this->distributions->reverse($this->distributions->find($id), $request->validated('reason')));
    }

    // ------------------------------------------------------- proof dan konfirmasi

    public function proofs(string $id): JsonResponse
    {
        return ApiResponse::data(DistributionProofResource::collection($this->distributions->find($id)->proofs));
    }

    public function storeProof(StoreDistributionProofRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(
            new DistributionProofResource($this->distributions->addProof($this->distributions->find($id), $request->validated())),
            status: 201
        );
    }

    public function verifyProof(string $id, string $proofId): JsonResponse
    {
        $proof = DistributionProof::where('distribution_id', $this->distributions->find($id)->id)->find($proofId)
            ?? throw ZakatException::notFound('Bukti tidak ditemukan.');

        return ApiResponse::data(new DistributionProofResource($this->distributions->verifyProof($proof)));
    }

    public function confirm(ConfirmDistributionRequest $request, string $id): JsonResponse
    {
        $this->distributions->confirm($this->distributions->find($id), $request->validated());

        return ApiResponse::data(new DistributionResource($this->distributions->find($id)));
    }

    // ---------------------------------------------------------------- requests

    public function requests(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(DistributionRequestResource::collection($this->distributions->requests($request->filters())));
    }

    public function storeRequest(StoreDistributionRequestRequest $request): JsonResponse
    {
        return ApiResponse::data(new DistributionRequestResource($this->distributions->createRequest($request->validated())), status: 201);
    }

    public function showRequest(string $id): JsonResponse
    {
        return ApiResponse::data(new DistributionRequestResource($this->distributions->findRequest($id)));
    }

    public function approveRequest(string $id): JsonResponse
    {
        return ApiResponse::data(new DistributionRequestResource(
            $this->distributions->approveRequest($this->distributions->findRequest($id))
        ));
    }

    public function rejectRequest(ReasonRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new DistributionRequestResource(
            $this->distributions->rejectRequest($this->distributions->findRequest($id), $request->validated('reason'))
        ));
    }

    /** Ringkasan status untuk dashboard PRD 12AD §67. */
    public function summary(): JsonResponse
    {
        $counts = Distribution::query()
            ->selectRaw('status, count(*) as total, coalesce(sum(distributed_amount), 0) as amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return ApiResponse::data([
            'by_status' => collect(DistributionStatus::cases())->mapWithKeys(fn ($status) => [
                $status->value => [
                    'total' => (int) ($counts[$status->value]->total ?? 0),
                    'distributed_amount' => (string) ($counts[$status->value]->amount ?? '0.00'),
                ],
            ]),
        ]);
    }

    private function respond(Distribution $distribution): JsonResponse
    {
        return ApiResponse::data(new DistributionResource($this->distributions->find($distribution->id)));
    }
}
