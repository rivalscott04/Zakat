<?php

namespace App\Enums;

/** PRD 13O §28. */
enum PaymentRefundStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Rejected = 'rejected';
}
