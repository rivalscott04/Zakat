<?php

namespace App\Enums;

/** PRD 16E §9. WhatsApp, SMS, dan push menyusul (FUTURE DEVELOPMENT). */
enum NotificationChannel: string
{
    case InApp = 'in_app';
    case Email = 'email';
    case Webhook = 'webhook';

    /** PRD 16O §32 — hanya channel eksternal yang wajib lewat queue. */
    public function isExternal(): bool
    {
        return $this !== self::InApp;
    }

    public function maxAttempts(): int
    {
        return (int) config('zakat.notification.max_attempts.'.$this->value, 3);
    }
}
