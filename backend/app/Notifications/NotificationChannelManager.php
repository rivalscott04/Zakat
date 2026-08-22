<?php

namespace App\Notifications;

use App\Enums\NotificationChannel;
use App\Exceptions\ZakatException;
use App\Notifications\Channels\EmailChannel;
use App\Notifications\Channels\InAppChannel;
use App\Notifications\Channels\WebhookChannel;
use App\Notifications\Contracts\NotificationChannelDriver;

/** PRD 16B §4 — pemetaan channel ke driver. */
class NotificationChannelManager
{
    /** @var array<string, class-string<NotificationChannelDriver>> */
    private array $drivers = [
        NotificationChannel::InApp->value => InAppChannel::class,
        NotificationChannel::Email->value => EmailChannel::class,
        NotificationChannel::Webhook->value => WebhookChannel::class,
    ];

    public function supports(NotificationChannel $channel): bool
    {
        return isset($this->drivers[$channel->value]);
    }

    public function for(NotificationChannel $channel): NotificationChannelDriver
    {
        $class = $this->drivers[$channel->value] ?? null;

        if ($class === null) {
            throw ZakatException::conflict("Channel notifikasi [{$channel->value}] belum tersedia.");
        }

        return app($class);
    }
}
