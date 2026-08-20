<?php

namespace App\Enums;

enum EligibilityStatus: string
{
    case Eligible = 'eligible';
    case NotEligible = 'not_eligible';
    case ReviewRequired = 'review_required';
    case Incomplete = 'incomplete';
}
