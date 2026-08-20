<?php

namespace App\Enums;

enum ZakatStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';
    case Expired = 'expired';
    case Archived = 'archived';
}
