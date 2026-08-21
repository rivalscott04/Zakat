<?php

namespace App\Enums;

/** PRD 13P §30. */
enum PaymentReconciliationResult: string
{
    case Matched = 'matched';
    case Mismatched = 'mismatched';
    case PendingReview = 'pending_review';
}
