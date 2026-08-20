<?php

namespace App\Enums;

/** PRD 02 §13 — status membership. */
enum MembershipStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Inactive = 'inactive';
    case Terminated = 'terminated';

    public function grantsAccess(): bool
    {
        return $this === self::Active;
    }
}
