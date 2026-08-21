<?php

namespace App\Enums;

/** PRD 13F §12 dan §13. */
enum PaymentStatus: string
{
    case Created = 'created';
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    /** @return array<int, self> */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Created => [self::Pending, self::Failed, self::Cancelled],
            // PRD 13U §6 — payment yang sudah paid tidak boleh kembali pending.
            self::Pending => [self::Paid, self::Failed, self::Expired, self::Cancelled],
            self::Paid => [self::Refunded],
            self::Failed, self::Expired, self::Cancelled, self::Refunded => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }

    public function isSettled(): bool
    {
        return $this === self::Paid || $this === self::Refunded;
    }
}
