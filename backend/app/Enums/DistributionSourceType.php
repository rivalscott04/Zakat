<?php

namespace App\Enums;

/** PRD 12E §11. */
enum DistributionSourceType: string
{
    case Program = 'program';
    case Direct = 'direct';
    case Emergency = 'emergency';
    case Campaign = 'campaign';
    case Other = 'other';
}
