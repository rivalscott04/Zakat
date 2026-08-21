<?php

namespace App\Enums;

/** PRD 13N §26. */
enum PaymentFailureReason: string
{
    case ProviderError = 'provider_error';
    case PaymentDeclined = 'payment_declined';
    case PaymentTimeout = 'payment_timeout';
    case InvalidAccount = 'invalid_account';
    case InvalidAmount = 'invalid_amount';
    case WebhookInvalid = 'webhook_invalid';
    case SignatureInvalid = 'signature_invalid';
    case Other = 'other';
}
