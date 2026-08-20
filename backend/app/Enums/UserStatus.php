<?php

namespace App\Enums;

/** PRD 01 §6 — status user. */
enum UserStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
    case Locked = 'locked';

    /** PRD 01 §49.1 sampai §49.3 — hanya user active yang boleh login. */
    public function canLogin(): bool
    {
        return $this === self::Active;
    }
}
