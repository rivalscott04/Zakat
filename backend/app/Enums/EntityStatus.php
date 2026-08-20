<?php

namespace App\Enums;

/** PRD 00 §15 — status lifecycle untuk entity non-transaksional. */
enum EntityStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';
}
