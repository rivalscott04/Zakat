<?php

namespace App\Models;

use App\Enums\AssignmentStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** PRD 02 §20 — assignment operasional amil. Bukan authorization. */
#[Fillable(['assignment_type', 'started_at'])]
class AmilAssignment extends Model
{
    use Auditable, BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return [
            'status' => AssignmentStatus::class,
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function amil(): BelongsTo
    {
        return $this->belongsTo(Amil::class);
    }

    public function auditPrefix(): string
    {
        return 'amil_assignment';
    }
}
