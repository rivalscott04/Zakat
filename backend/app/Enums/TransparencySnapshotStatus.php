<?php

namespace App\Enums;

/** PRD 18F §9 dan PRD 18G §11. */
enum TransparencySnapshotStatus: string
{
    case Draft = 'DRAFT';
    case Generated = 'GENERATED';
    case PendingApproval = 'PENDING_APPROVAL';
    case Approved = 'APPROVED';
    case Published = 'PUBLISHED';
    case Archived = 'ARCHIVED';
    case Revoked = 'REVOKED';

    /** @return array<int, self> */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Draft => [self::Generated],
            // Regenerate diizinkan selama belum diajukan.
            self::Generated => [self::Generated, self::PendingApproval],
            self::PendingApproval => [self::Approved, self::Generated],
            self::Approved => [self::Published, self::PendingApproval],
            // PRD 18Z §14 — snapshot terbit tidak boleh diedit, hanya dicabut atau diarsipkan.
            self::Published => [self::Revoked, self::Archived],
            self::Archived, self::Revoked => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }

    public function isPublic(): bool
    {
        return $this === self::Published;
    }
}
