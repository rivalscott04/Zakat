<?php

namespace App\Enums;

/** PRD 16F §12. */
enum NotificationStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Queued = 'queued';
    case PartiallySent = 'partially_sent';
    case Sent = 'sent';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function isFinal(): bool
    {
        return in_array($this, [self::Sent, self::Failed, self::Cancelled], true);
    }
}
