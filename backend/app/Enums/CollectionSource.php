<?php

namespace App\Enums;

enum CollectionSource: string
{
    case Calculator = 'calculator';
    case Manual = 'manual';
    case SelfService = 'self_service';
    case Import = 'import';
    case Api = 'api';
    case Integration = 'integration';
}
