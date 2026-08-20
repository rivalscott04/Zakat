<?php

namespace App\Enums;

/** PRD 12S §47. */
enum DistributionConfirmationMethod: string
{
    case Signature = 'signature';
    case Otp = 'otp';
    case Qr = 'qr';
    case Photo = 'photo';
    case Manual = 'manual';
    case Other = 'other';
}
