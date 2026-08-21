<?php

namespace App\Enums;

/** PRD 13U §4 — hanya provider aktif yang boleh dipakai membuat payment. */
enum PaymentProviderStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
