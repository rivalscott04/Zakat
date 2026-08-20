<?php

namespace App\Enums;

/** PRD 12D §7. */
enum DistributionType: string
{
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case Goods = 'goods';
    case Service = 'service';
    case Voucher = 'voucher';
    case Scholarship = 'scholarship';
    case BusinessCapital = 'business_capital';
    case Emergency = 'emergency';
    case Other = 'other';

    /** PRD 12L §30 dan 12M — tipe yang wajib punya detail realisasi. */
    public function requiresCashDetail(): bool
    {
        return $this === self::Cash;
    }

    public function requiresBankTransfer(): bool
    {
        return $this === self::BankTransfer;
    }
}
