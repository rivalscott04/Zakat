<?php

namespace App\Enums;

/** PRD 00 §15 — status lifecycle untuk entity transaksional. */
enum TransactionStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Verified = 'verified';
    case Posted = 'posted';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Reversed = 'reversed';

    /** @return array<int, self> */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Draft => [self::Pending, self::Cancelled],
            self::Pending => [self::Verified, self::Cancelled],
            self::Verified => [self::Posted, self::Cancelled],
            self::Posted => [self::Completed, self::Reversed],
            self::Completed => [self::Reversed],
            self::Cancelled, self::Reversed => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }
}
