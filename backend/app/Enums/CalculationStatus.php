<?php

namespace App\Enums;

enum CalculationStatus: string
{
    case Draft = 'draft';
    case Calculated = 'calculated';
    case Confirmed = 'confirmed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Converted = 'converted';
}
