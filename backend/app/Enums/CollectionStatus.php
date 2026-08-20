<?php

namespace App\Enums;

enum CollectionStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Completed = 'completed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
}
