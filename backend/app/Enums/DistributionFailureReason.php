<?php

namespace App\Enums;

/** PRD 12T §48. */
enum DistributionFailureReason: string
{
    case BankAccountInvalid = 'bank_account_invalid';
    case RecipientNotFound = 'recipient_not_found';
    case RecipientRejected = 'recipient_rejected';
    case InsufficientFund = 'insufficient_fund';
    case TransferFailed = 'transfer_failed';
    case DocumentMissing = 'document_missing';
    case VerificationFailed = 'verification_failed';
    case SystemError = 'system_error';
    case Other = 'other';
}
