<?php

namespace App\Enums;

enum CollectionPaymentStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Settled = 'settled';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
}
