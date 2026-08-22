<?php

namespace App\Notifications\Channels;

use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Notifications\Contracts\NotificationChannelDriver;

/**
 * PRD 16G — in app notification. Isinya sudah tersimpan pada baris notification,
 * jadi pengiriman hanya menandai delivery sebagai sampai.
 */
class InAppChannel implements NotificationChannelDriver
{
    public function resolveAddress(Notification $notification): ?string
    {
        return $notification->recipient_id;
    }

    public function send(NotificationDelivery $delivery): array
    {
        return ['provider' => 'in_app', 'provider_reference' => $delivery->notification_id, 'delivered' => true];
    }
}
