<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ReportExportFormat;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExportReportRunRequest;
use App\Http\Requests\ListRequest;
use App\Http\Resources\ReportRunResource;
use App\Services\ReportExportService;
use App\Services\ReportRunService;
use App\Services\ReportService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** PRD 19R §44 dan §45. */
class ReportRunController extends Controller
{
    public function __construct(
        private readonly ReportRunService $runs,
        private readonly ReportExportService $exports,
        private readonly ReportService $reports,
    ) {}

    public function index(ListRequest $request): JsonResponse
    {
        $filters = $request->validated() + ['report_id' => $request->query('report_id')];

        return ApiResponse::data(ReportRunResource::collection($this->runs->list($filters)));
    }

    public function show(string $id): JsonResponse
    {
        $run = $this->runs->find($id);
        $this->reports->assertAccessible($run->report);

        return ApiResponse::data(new ReportRunResource($run, withSnapshot: true));
    }

    public function cancel(string $id): JsonResponse
    {
        return ApiResponse::data(new ReportRunResource($this->runs->cancel($this->runs->find($id))->load('report')));
    }

    public function retry(string $id): JsonResponse
    {
        $run = $this->runs->find($id);
        $this->reports->assertAccessible($run->report);

        return ApiResponse::data(new ReportRunResource($this->runs->retry($run)->load('report'), withSnapshot: true));
    }

    public function export(ExportReportRunRequest $request, string $id): JsonResponse
    {
        $run = $this->runs->find($id);
        $this->reports->assertAccessible($run->report);

        $export = $this->exports->export($run, ReportExportFormat::from($request->validated('format')));

        return ApiResponse::data([
            'id' => $export->getKey(),
            'format' => $export->format->value,
            'file_size' => $export->file_size,
            'expires_at' => $export->expires_at?->toIso8601String(),
        ], status: 201);
    }

    public function showExport(string $id): JsonResponse
    {
        $export = $this->exports->find($id);
        $this->reports->assertAccessible($export->run->report);

        return ApiResponse::data([
            'id' => $export->getKey(),
            'run_number' => $export->run->run_number,
            'format' => $export->format->value,
            'file_size' => $export->file_size,
            'expires_at' => $export->expires_at?->toIso8601String(),
            'download_count' => $export->download_count,
        ]);
    }

    public function download(Request $request, string $id): BinaryFileResponse
    {
        $export = $this->exports->find($id);
        $this->reports->assertAccessible($export->run->report);

        $file = $this->exports->download($export);

        return response()->download($file['path'], $file['name'], ['Content-Type' => $file['mime']]);
    }
}
