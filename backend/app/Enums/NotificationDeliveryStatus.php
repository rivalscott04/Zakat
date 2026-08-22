<?php

namespace App\Enums;

/** PRD 16F §11. */
enum NotificationDeliveryStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Processing = 'processing';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function isSuccessful(): bool
    {
        return $this === self::Sent || $this === self::Delivered;
    }

    public function isFinal(): bool
    {
        return $this->isSuccessful() || $this === self::Failed || $this === self::Cancelled;
    }
}
