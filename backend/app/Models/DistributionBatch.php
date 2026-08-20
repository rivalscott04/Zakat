<?php

namespace App\Models;

use App\Enums\DistributionBatchStatus;
use App\Enums\DistributionType;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** PRD 12P §39. */
#[Fillable(['batch_number', 'name', 'program_id', 'fund_id', 'distribution_type', 'total_amount', 'total_beneficiary', 'status', 'created_by'])]
class DistributionBatch extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return ['distribution_type' => DistributionType::class, 'total_amount' => 'decimal:2', 'status' => DistributionBatchStatus::class, 'approved_at' => 'datetime'];
    }

    public function beneficiaries()
    {
        return $this->hasMany(DistributionBeneficiary::class, 'batch_id');
    }

    public function fund()
    {
        return $this->belongsTo(Fund::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}
