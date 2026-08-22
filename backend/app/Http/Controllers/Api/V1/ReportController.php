<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EntityStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListRequest;
use App\Http\Requests\RunReportRequest;
use App\Http\Requests\StoreReportRequest;
use App\Http\Resources\ReportResource;
use App\Http\Resources\ReportRunResource;
use App\Services\ReportRunService;
use App\Services\ReportService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** PRD 19R §43, §44, dan §48. */
class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly ReportRunService $runs,
    ) {}

    public function index(ListRequest $request): JsonResponse
    {
        $filters = $request->validated() + ['category' => $request->query('category')];

        return ApiResponse::data(ReportResource::collection($this->reports->list($filters)));
    }

    public function favorites(): JsonResponse
    {
        return ApiResponse::data(ReportResource::collection($this->reports->favorites()));
    }

    public function show(string $id): JsonResponse
    {
        $report = $this->reports->find($id);
        $this->reports->assertAccessible($report);

        return ApiResponse::data(new ReportResource($report));
    }

    public function store(StoreReportRequest $request): JsonResponse
    {
        return ApiResponse::data(new ReportResource($this->reports->create($request->validated())), status: 201);
    }

    public function update(StoreReportRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new ReportResource($this->reports->update($this->reports->find($id), $request->validated())));
    }

    public function activate(string $id): JsonResponse
    {
        return ApiResponse::data(new ReportResource($this->reports->setStatus($this->reports->find($id), EntityStatus::Active)));
    }

    public function deactivate(string $id): JsonResponse
    {
        return ApiResponse::data(new ReportResource($this->reports->setStatus($this->reports->find($id), EntityStatus::Inactive)));
    }

    public function run(RunReportRequest $request, string $id): JsonResponse
    {
        $report = $this->reports->find($id);
        $this->reports->assertAccessible($report);

        $run = $this->runs->run($report, (array) $request->validated('parameters', []), (bool) $request->validated('queue', false));

        return ApiResponse::data(new ReportRunResource($run->load('report'), withSnapshot: true), status: 201);
    }

    public function favorite(string $id): JsonResponse
    {
        $this->reports->addFavorite($this->reports->find($id));

        return ApiResponse::noContent();
    }

    public function unfavorite(string $id): JsonResponse
    {
        $this->reports->removeFavorite($this->reports->find($id));

        return ApiResponse::noContent();
    }

    /** PRD 19Q §42 — ringkasan untuk dashboard reporting. */
    public function dashboard(Request $request): JsonResponse
    {
        $recent = $this->runs->list(['per_page' => 5]);

        return ApiResponse::data([
            'available_reports' => ReportResource::collection($this->reports->list(['per_page' => 100])->items()),
            'favorites' => ReportResource::collection($this->reports->favorites()),
            'recent_runs' => ReportRunResource::collection($recent->items()),
            'failed_runs' => ReportRunResource::collection($this->runs->list(['status' => 'FAILED', 'per_page' => 5])->items()),
        ]);
    }
}
