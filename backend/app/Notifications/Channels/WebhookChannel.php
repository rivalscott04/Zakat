<?php

namespace App\Notifications\Channels;

use App\Enums\EntityStatus;
use App\Exceptions\ZakatException;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\NotificationWebhook;
use App\Notifications\Contracts\NotificationChannelDriver;
use Illuminate\Support\Facades\Http;

/** PRD 16I — webhook keluar dengan tanda tangan HMAC (PRD 16I §21). */
class WebhookChannel implements NotificationChannelDriver
{
    public const SIGNATURE_HEADER = 'X-ZakatOS-Signature';

    public function resolveAddress(Notification $notification): ?string
    {
        return $this->endpointFor($notification)?->url;
    }

    public function send(NotificationDelivery $delivery): array
    {
        $notification = $delivery->notification;
        $endpoint = $this->endpointFor($notification);

        if ($endpoint === null) {
            throw ZakatException::conflict('Tidak ada webhook aktif untuk event ini.');
        }

        $payload = [
            'notification_number' => $notification->notification_number,
            'event_name' => $notification->event_name,
            'title' => $notification->title,
            'message' => $notification->message,
            'data' => $notification->data,
            'occurred_at' => $notification->created_at?->toIso8601String(),
        ];

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $headers = ['Content-Type' => 'application/json'];

        // Endpoint tanpa secret tetap dilayani, tetapi tanpa tanda tangan
        // penerima tidak punya cara memverifikasi asal payload.
        if ($endpoint->secret_encrypted) {
            $headers[self::SIGNATURE_HEADER] = hash_hmac('sha256', $body, $endpoint->secret_encrypted);
        }

        $response = Http::withHeaders($headers)->timeout(10)->withBody($body, 'application/json')->post($endpoint->url);

        if ($response->failed()) {
            throw ZakatException::conflict("Webhook menolak dengan status {$response->status()}.");
        }

        return ['provider' => 'webhook', 'provider_reference' => $endpoint->getKey(), 'delivered' => true];
    }

    private function endpointFor(Notification $notification): ?NotificationWebhook
    {
        return NotificationWebhook::query()
            ->acrossOrganizations()
            ->where('organization_id', $notification->organization_id)
            ->where('status', EntityStatus::Active)
            ->get()
            ->first(fn (NotificationWebhook $webhook) => blank($webhook->events)
                || in_array($notification->event_name, $webhook->events, true));
    }
}
