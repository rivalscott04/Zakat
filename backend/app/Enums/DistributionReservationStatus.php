<?php

namespace App\Enums;

/** PRD 12H §21. */
enum DistributionReservationStatus: string
{
    case Active = 'active';
    case Released = 'released';
    case Consumed = 'consumed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
}
