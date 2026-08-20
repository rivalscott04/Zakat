<?php

namespace App\Enums;

/** PRD 12P §41 — alur batch distribution. */
enum DistributionBatchStatus: string
{
    case Draft = 'draft';
    case Validated = 'validated';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Processing = 'processing';
    case Completed = 'completed';
    case PartiallyCompleted = 'partially_completed';
    case Cancelled = 'cancelled';

    /** @return array<int, self> */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Draft => [self::Validated, self::Cancelled],
            self::Validated => [self::PendingApproval, self::Draft, self::Cancelled],
            self::PendingApproval => [self::Approved, self::Draft, self::Cancelled],
            self::Approved => [self::Processing, self::Cancelled],
            self::Processing => [self::Completed, self::PartiallyCompleted],
            self::PartiallyCompleted => [self::Processing, self::Completed],
            self::Completed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }
}
