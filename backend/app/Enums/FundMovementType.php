<?php

namespace App\Enums;

enum FundMovementType: string
{
    case CollectionInflow = 'collection_inflow';
    case Allocation = 'allocation';
    case Reservation = 'reservation';
    case ReservationRelease = 'reservation_release';
    case Distribution = 'distribution';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case Adjustment = 'adjustment';
    case Reversal = 'reversal';
    case OpeningBalance = 'opening_balance';
}
