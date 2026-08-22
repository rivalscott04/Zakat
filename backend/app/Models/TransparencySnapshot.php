<?php

namespace App\Models;

use App\Enums\TransparencySnapshotStatus;
use App\Enums\TransparencySnapshotType;
use App\Enums\TransparencyVerificationStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasBusinessNumber;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** PRD 18D §6. */
#[Fillable(['period_start', 'period_end', 'snapshot_type'])]
class TransparencySnapshot extends Model
{
    use BelongsToOrganization, HasBusinessNumber, HasUlids;

    public static function businessCode(): string
    {
        return 'TRP';
    }

    public function businessNumberColumn(): string
    {
        return 'snapshot_number';
    }

    protected function casts(): array
    {
        return [
            'snapshot_type' => TransparencySnapshotType::class,
            'status' => TransparencySnapshotStatus::class,
            'verification_status' => TransparencyVerificationStatus::class,
            'data' => 'array',
            'verification_notes' => 'array',
            'period_start' => 'date',
            'period_end' => 'date',
            'generated_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
