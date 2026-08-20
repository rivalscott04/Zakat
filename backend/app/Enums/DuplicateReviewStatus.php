<?php

namespace App\Enums;

/** PRD 03 §35 — hasil review duplikasi. */
enum DuplicateReviewStatus: string
{
    case Pending = 'pending';
    case ConfirmedDuplicate = 'confirmed_duplicate';
    case NotDuplicate = 'not_duplicate';
    case Ignored = 'ignored';
}
