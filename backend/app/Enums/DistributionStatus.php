<?php

namespace App\Enums;

/** PRD 12J §25 dan §26 — status dan transisi yang diizinkan. */
enum DistributionStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Reserved = 'reserved';
    case Scheduled = 'scheduled';
    case Processing = 'processing';
    case Completed = 'completed';
    case PartiallyCompleted = 'partially_completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Reversed = 'reversed';

    /** @return array<int, self> */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Draft => [self::PendingApproval, self::Cancelled],
            self::PendingApproval => [self::Approved, self::Draft, self::Cancelled],
            self::Approved => [self::Reserved, self::Cancelled],
            self::Reserved => [self::Scheduled, self::Processing, self::Cancelled],
            self::Scheduled => [self::Processing, self::Cancelled],
            // PRD 12U §50 — apa pun yang belum completed masih boleh dibatalkan.
            self::Processing => [self::Completed, self::PartiallyCompleted, self::Failed, self::Cancelled],
            // Sebagian dana sudah keluar, jadi koreksinya lewat reversal, bukan cancel.
            self::PartiallyCompleted => [self::Processing, self::Completed, self::Reversed],
            self::Failed => [self::Processing, self::Cancelled],
            self::Completed => [self::Reversed],
            self::Cancelled, self::Reversed => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }

    /** Status akhir yang tidak boleh diubah lagi (PRD 12U §50, 12V §51). */
    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::Reversed], true);
    }

    /** Dana masih tertahan pada reservation selama status berikut. */
    public function holdsReservation(): bool
    {
        return in_array($this, [self::Reserved, self::Scheduled, self::Processing, self::Failed], true);
    }
}
