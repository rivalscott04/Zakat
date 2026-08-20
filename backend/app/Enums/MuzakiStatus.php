<?php

namespace App\Enums;

/** PRD 03 §8 — lifecycle Muzaki. */
enum MuzakiStatus: string
{
    case Lead = 'lead';
    case Active = 'active';
    case Inactive = 'inactive';
    case Blocked = 'blocked';
    case Archived = 'archived';
}
