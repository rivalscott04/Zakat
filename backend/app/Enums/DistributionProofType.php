<?php

namespace App\Enums;

/** PRD 12R §45. */
enum DistributionProofType: string
{
    case Signature = 'signature';
    case Photo = 'photo';
    case Receipt = 'receipt';
    case BankTransfer = 'bank_transfer';
    case Document = 'document';
    case QrConfirmation = 'qr_confirmation';
    case Other = 'other';
}
