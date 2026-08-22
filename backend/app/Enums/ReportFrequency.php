<?php

namespace App\Enums;

use Carbon\CarbonInterface;

/** PRD 19K §34. */
enum ReportFrequency: string
{
    case Daily = 'DAILY';
    case Weekly = 'WEEKLY';
    case Monthly = 'MONTHLY';
    case Quarterly = 'QUARTERLY';
    case Yearly = 'YEARLY';

    public function next(CarbonInterface $from): CarbonInterface
    {
        return match ($this) {
            self::Daily => $from->copy()->addDay(),
            self::Weekly => $from->copy()->addWeek(),
            self::Monthly => $from->copy()->addMonth(),
            self::Quarterly => $from->copy()->addMonths(3),
            self::Yearly => $from->copy()->addYear(),
        };
    }
}
