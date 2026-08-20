<?php

namespace App\Enums;

/** PRD 12M §32. */
enum BankTransferStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Success = 'success';
    case Failed = 'failed';
    case Reversed = 'reversed';
}
