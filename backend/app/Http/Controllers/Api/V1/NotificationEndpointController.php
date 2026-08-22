<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveNotificationEmailConfigRequest;
use App\Http\Requests\StoreNotificationWebhookRequest;
use App\Models\NotificationWebhook;
use App\Services\NotificationEndpointService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * PRD 16H dan 16I — konfigurasi tujuan email dan webhook.
 *
 * Secret hanya muncul sekali pada response pembuatan atau rotasi; setelah itu
 * tidak pernah dapat dibaca kembali (PRD 16H §18).
 */
class NotificationEndpointController extends Controller
{
    public function __construct(private readonly NotificationEndpointService $endpoints) {}

    public function webhooks(): JsonResponse
    {
        return ApiResponse::data($this->endpoints->webhooks()->map($this->present(...)));
    }

    public function storeWebhook(StoreNotificationWebhookRequest $request): JsonResponse
    {
        $result = $this->endpoints->createWebhook($request->validated());

        return ApiResponse::data($this->present($result['webhook']) + ['secret' => $result['secret']], status: 201);
    }

    public function updateWebhook(StoreNotificationWebhookRequest $request, string $id): JsonResponse
    {
        $webhook = NotificationWebhook::query()->findOrFail($id);

        return ApiResponse::data($this->present($this->endpoints->updateWebhook($webhook, $request->validated())));
    }

    public function rotateWebhookSecret(string $id): JsonResponse
    {
        $result = $this->endpoints->rotateWebhookSecret(NotificationWebhook::query()->findOrFail($id));

        return ApiResponse::data($this->present($result['webhook']) + ['secret' => $result['secret']]);
    }

    public function emailConfig(): JsonResponse
    {
        $config = $this->endpoints->emailConfig();

        return ApiResponse::data($config === null ? null : [
            'id' => $config->id,
            'driver' => $config->driver,
            'host' => $config->host,
            'port' => $config->port,
            'from_name' => $config->from_name,
            'from_email' => $config->from_email,
            'encryption' => $config->encryption,
            'status' => $config->status->value,
            // PRD 16H §18 — credential tidak pernah ditampilkan penuh.
            'username_masked' => $config->username_encrypted === null ? null : $this->mask($config->username_encrypted),
            'has_password' => $config->password_encrypted !== null,
        ]);
    }

    public function saveEmailConfig(SaveNotificationEmailConfigRequest $request): JsonResponse
    {
        $this->endpoints->saveEmailConfig($request->validated());

        return $this->emailConfig();
    }

    private function present(NotificationWebhook $webhook): array
    {
        return [
            'id' => $webhook->id,
            'name' => $webhook->name,
            'url' => $webhook->url,
            'events' => $webhook->events ?? [],
            'status' => $webhook->status->value,
            'has_secret' => $webhook->secret_encrypted !== null,
        ];
    }

    private function mask(string $value): string
    {
        return substr($value, 0, 2).str_repeat('*', max(strlen($value) - 2, 3));
    }
}
