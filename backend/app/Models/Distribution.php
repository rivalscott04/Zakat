<?php

namespace App\Models;

use App\Enums\DistributionSourceType;
use App\Enums\DistributionStatus;
use App\Enums\DistributionType;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * PRD 12C §5 — distribution.
 *
 * Kolom nominal, status, dan jejak approval sengaja tidak fillable: seluruhnya
 * ditentukan Service Layer, tidak pernah dari payload client (CLAUDE.md §34).
 */
#[Fillable(['distribution_type', 'source_type', 'program_id', 'program_enrollment_id', 'mustahik_id', 'assessment_id', 'fund_id', 'requested_amount', 'scheduled_date', 'priority', 'description'])]
class Distribution extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return [
            'distribution_type' => DistributionType::class,
            'source_type' => DistributionSourceType::class,
            'status' => DistributionStatus::class,
            'requested_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'distributed_amount' => 'decimal:2',
            'distribution_date' => 'date',
            'scheduled_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'reversed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function mustahik()
    {
        return $this->belongsTo(Mustahik::class);
    }

    public function fund()
    {
        return $this->belongsTo(Fund::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function items()
    {
        return $this->hasMany(DistributionItem::class);
    }

    public function reservations()
    {
        return $this->hasMany(DistributionReservation::class);
    }

    public function activeReservation()
    {
        return $this->hasOne(DistributionReservation::class)->where('status', 'active');
    }

    public function cashDetails()
    {
        return $this->hasMany(DistributionCashDetail::class);
    }

    public function bankTransfers()
    {
        return $this->hasMany(DistributionBankTransfer::class);
    }

    public function schedules()
    {
        return $this->hasMany(DistributionSchedule::class);
    }

    public function proofs()
    {
        return $this->hasMany(DistributionProof::class);
    }

    public function confirmation()
    {
        return $this->hasOne(DistributionConfirmation::class)->where('status', 'confirmed');
    }

    /** PRD 12O §37 — sisa nominal yang masih boleh direalisasikan. */
    public function remainingAmount(): string
    {
        return bcsub((string) $this->approved_amount, (string) $this->distributed_amount, 2);
    }
}
