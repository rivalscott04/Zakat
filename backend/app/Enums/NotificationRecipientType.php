<?php

namespace App\Enums;

/** PRD 16D §7. Versi awal fokus pada USER. */
enum NotificationRecipientType: string
{
    case User = 'user';
    case Mustahik = 'mustahik';
    case Muzaki = 'muzaki';
    case Organization = 'organization';
    case Other = 'other';
}
