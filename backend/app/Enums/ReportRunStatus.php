<?php

namespace App\Enums;

/** PRD 19H §25. */
enum ReportRunStatus: string
{
    case Queued = 'QUEUED';
    case Processing = 'PROCESSING';
    case Completed = 'COMPLETED';
    case Failed = 'FAILED';
    case Cancelled = 'CANCELLED';

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Failed, self::Cancelled], true);
    }
}
