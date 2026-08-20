<?php

namespace App\Enums;

/** PRD 12N §35. */
enum DistributionScheduleType: string
{
    case OneTime = 'one_time';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Custom = 'custom';
}
