<?php

namespace App\Enums;

/** PRD 13E §11. Ketersediaan sebenarnya tergantung provider. */
enum PaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case VirtualAccount = 'virtual_account';
    case Ewallet = 'ewallet';
    case Qris = 'qris';
    case Card = 'card';
    case Cash = 'cash';
    case Manual = 'manual';
    case Other = 'other';
}
