<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SessionResource;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** PRD 01 §36 — session endpoints. */
class SessionController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::data(SessionResource::collection($this->auth->sessions($request->user())));
    }

    public function destroy(Request $request, string $sessionId): JsonResponse
    {
        $this->auth->revokeSession($request->user(), $sessionId);

        // Mencabut session sendiri berarti logout.
        if ($sessionId === $request->session()->getId()) {
            $this->auth->logout($request);
        }

        return ApiResponse::data(['message' => 'Session dicabut.']);
    }

    /** PRD 01 §36 — session yang sedang dipakai tidak ikut dihapus. */
    public function destroyOthers(Request $request): JsonResponse
    {
        $count = $this->auth->revokeOtherSessions($request->user(), $request->session()->getId());

        return ApiResponse::data(['revoked' => $count]);
    }
}
