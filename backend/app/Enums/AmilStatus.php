<?php

namespace App\Enums;

/** PRD 02 §19 — status amil. */
enum AmilStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
    case Ended = 'ended';

    /** PRD 02 §37.4 — amil ended tidak dapat menerima assignment baru. */
    public function canReceiveAssignment(): bool
    {
        return $this === self::Active;
    }
}
