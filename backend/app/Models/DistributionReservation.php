<?php

namespace App\Models;

use App\Enums\DistributionReservationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** PRD 12H §20 — proyeksi reservation dana pada sisi Distribution. */
#[Fillable(['distribution_id', 'fund_id', 'fund_reservation_id', 'reserved_amount', 'currency', 'reserved_at', 'released_at', 'status'])]
class DistributionReservation extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return ['reserved_amount' => 'decimal:2', 'reserved_at' => 'datetime', 'released_at' => 'datetime', 'status' => DistributionReservationStatus::class];
    }

    public function distribution()
    {
        return $this->belongsTo(Distribution::class);
    }

    public function fundReservation()
    {
        return $this->belongsTo(FundReservation::class, 'fund_reservation_id');
    }
}
