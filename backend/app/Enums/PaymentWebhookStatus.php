<?php

namespace App\Enums;

/** PRD 13I §19. */
enum PaymentWebhookStatus: string
{
    case Received = 'received';
    case Processing = 'processing';
    case Processed = 'processed';
    case Failed = 'failed';
    case Ignored = 'ignored';
}
