<?php

namespace App\Enums;

/** PRD 02 §20 — status amil assignment. */
enum AssignmentStatus: string
{
    case Active = 'active';
    case Ended = 'ended';
}
