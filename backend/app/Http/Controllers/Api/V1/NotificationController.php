<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationFilterRequest;
use App\Http\Requests\UpdateNotificationPreferencesRequest;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationPreferenceService;
use App\Services\NotificationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/** PRD 16T — notification center milik user yang sedang login. */
class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly NotificationPreferenceService $preferences,
    ) {}

    public function index(NotificationFilterRequest $request): JsonResponse
    {
        return ApiResponse::data(
            NotificationResource::collection($this->notifications->listForCurrentUser($request->validated())),
            ['unread_count' => $this->notifications->unreadCount()],
        );
    }

    public function unreadCount(): JsonResponse
    {
        return ApiResponse::data(['unread_count' => $this->notifications->unreadCount()]);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::data(new NotificationResource($this->notifications->findForCurrentUser($id)));
    }

    public function read(string $id): JsonResponse
    {
        return ApiResponse::data(new NotificationResource($this->notifications->markRead($id)));
    }

    public function unread(string $id): JsonResponse
    {
        return ApiResponse::data(new NotificationResource($this->notifications->markUnread($id)));
    }

    public function readAll(): JsonResponse
    {
        return ApiResponse::data(['marked' => $this->notifications->markAllRead()]);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->notifications->delete($id);

        return ApiResponse::noContent();
    }

    public function preferences(): JsonResponse
    {
        return ApiResponse::data($this->preferences->forCurrentUser());
    }

    public function updatePreferences(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        return ApiResponse::data($this->preferences->update($request->validated('preferences')));
    }
}
