<?php

namespace App\Models;

use App\Enums\DistributionProofType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** PRD 12R §44. */
#[Fillable(['distribution_id', 'proof_type', 'file_id', 'reference_number', 'note', 'uploaded_by', 'verified_by', 'verified_at'])]
class DistributionProof extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return ['proof_type' => DistributionProofType::class, 'verified_at' => 'datetime'];
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
