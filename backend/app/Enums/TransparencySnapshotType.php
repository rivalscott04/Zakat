<?php

namespace App\Enums;

/** PRD 18E §8. */
enum TransparencySnapshotType: string
{
    case Daily = 'DAILY';
    case Monthly = 'MONTHLY';
    case Quarterly = 'QUARTERLY';
    case Yearly = 'YEARLY';
    case Custom = 'CUSTOM';
}
