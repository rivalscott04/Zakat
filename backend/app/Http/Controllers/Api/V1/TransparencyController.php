<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListRequest;
use App\Http\Requests\RevokeTransparencySnapshotRequest;
use App\Http\Requests\StoreTransparencyReportRequest;
use App\Http\Requests\StoreTransparencySnapshotRequest;
use App\Http\Resources\TransparencyReportResource;
use App\Http\Resources\TransparencySnapshotResource;
use App\Services\TransparencyAggregator;
use App\Services\TransparencyService;
use App\Support\ApiResponse;
use App\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** PRD 18V §36, §37, dan §38 — sisi internal modul transparansi. */
class TransparencyController extends Controller
{
    public function __construct(
        private readonly TransparencyService $transparency,
        private readonly TransparencyAggregator $aggregator,
    ) {}

    /** PRD 18Q §27 — pengguna internal melihat angka terkini, bukan hanya yang terbit. */
    public function dashboard(Request $request): JsonResponse
    {
        $start = $request->query('period_start', now()->startOfYear()->toDateString());
        $end = $request->query('period_end', now()->toDateString());

        return ApiResponse::data($this->aggregator->build(OrganizationContext::requireId(), $start, $end));
    }

    public function index(ListRequest $request): JsonResponse
    {
        $filters = $request->validated() + ['snapshot_type' => $request->query('snapshot_type')];

        return ApiResponse::data(TransparencySnapshotResource::collection($this->transparency->list($filters)));
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::data(new TransparencySnapshotResource($this->transparency->find($id), withData: true));
    }

    public function store(StoreTransparencySnapshotRequest $request): JsonResponse
    {
        return ApiResponse::data(new TransparencySnapshotResource($this->transparency->create($request->validated())), status: 201);
    }

    public function generate(string $id): JsonResponse
    {
        return ApiResponse::data(new TransparencySnapshotResource(
            $this->transparency->generate($this->transparency->find($id)), withData: true
        ));
    }

    public function validateSnapshot(string $id): JsonResponse
    {
        return ApiResponse::data(new TransparencySnapshotResource($this->transparency->validateData($this->transparency->find($id))));
    }

    public function submit(string $id): JsonResponse
    {
        return ApiResponse::data(new TransparencySnapshotResource($this->transparency->submit($this->transparency->find($id))));
    }

    public function approve(string $id): JsonResponse
    {
        return ApiResponse::data(new TransparencySnapshotResource($this->transparency->approve($this->transparency->find($id))));
    }

    public function publish(string $id): JsonResponse
    {
        return ApiResponse::data(new TransparencySnapshotResource($this->transparency->publish($this->transparency->find($id))));
    }

    public function revoke(RevokeTransparencySnapshotRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new TransparencySnapshotResource(
            $this->transparency->revoke($this->transparency->find($id), $request->validated('reason'))
        ));
    }

    // --------------------------------------------------------------- laporan

    public function reports(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(TransparencyReportResource::collection($this->transparency->reports($request->validated())));
    }

    public function showReport(string $id): JsonResponse
    {
        return ApiResponse::data(new TransparencyReportResource($this->transparency->findReport($id)));
    }

    public function storeReport(StoreTransparencyReportRequest $request): JsonResponse
    {
        return ApiResponse::data(new TransparencyReportResource($this->transparency->createReport($request->validated())), status: 201);
    }

    public function publishReport(string $id): JsonResponse
    {
        return ApiResponse::data(new TransparencyReportResource($this->transparency->publishReport($this->transparency->findReport($id))));
    }

    public function archiveReport(string $id): JsonResponse
    {
        return ApiResponse::data(new TransparencyReportResource($this->transparency->archiveReport($this->transparency->findReport($id))));
    }
}
