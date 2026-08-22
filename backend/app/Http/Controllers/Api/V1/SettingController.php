<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Services\SettingService;
use App\Support\ApiResponse;
use App\Support\OrganizationContext;
use App\Support\SettingRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** PRD 20 — API System Settings. */
class SettingController extends Controller
{
    public function __construct(private readonly SettingService $settings) {}

    public function index(): JsonResponse
    {
        return ApiResponse::data($this->settings->describe(OrganizationContext::id()));
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        return ApiResponse::data(
            $this->settings->update($request->validated('values'), $request->validated('scope'))
        );
    }

    public function reset(Request $request, string $key): JsonResponse
    {
        return ApiResponse::data(
            $this->settings->reset($key, SettingRegistry::scopeOf($key) ?? SettingRegistry::ORGANIZATION)
        );
    }
}
