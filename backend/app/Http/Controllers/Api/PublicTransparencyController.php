<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PublicTransparencyService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * PRD 18V §39 — API publik tanpa autentikasi.
 *
 * Seluruh keluaran berasal dari snapshot berstatus PUBLISHED (PRD 18Z §13) dan
 * hanya berisi angka agregat.
 */
class PublicTransparencyController extends Controller
{
    public function __construct(private readonly PublicTransparencyService $transparency) {}

    public function dashboard(string $organization): JsonResponse
    {
        return ApiResponse::data($this->transparency->dashboard($organization));
    }

    public function summary(string $organization): JsonResponse
    {
        $dashboard = $this->transparency->dashboard($organization);

        return ApiResponse::data([
            'organization' => $dashboard['organization'],
            'period' => $dashboard['period'],
            'last_updated' => $dashboard['last_updated'],
            'collection' => $dashboard['data']['collection'] ?? null,
            'distribution' => $dashboard['data']['distribution'] ?? null,
            'fund' => $dashboard['data']['fund'] ?? null,
            'metrics' => $dashboard['data']['metrics'] ?? null,
        ]);
    }

    public function programs(string $organization): JsonResponse
    {
        return ApiResponse::data($this->transparency->dashboard($organization)['data']['programs'] ?? []);
    }

    public function reports(string $organization): JsonResponse
    {
        return ApiResponse::data($this->transparency->dashboard($organization)['reports']);
    }

    public function verify(string $reference): JsonResponse
    {
        return ApiResponse::data($this->transparency->verify($reference));
    }
}
