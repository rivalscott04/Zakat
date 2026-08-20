<?php

namespace App\Models;

use App\Enums\MembershipStatus;
use App\Enums\MemberType;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** PRD 02 §11 — membership user pada organisasi. */
#[Fillable(['member_type'])]
class OrganizationMember extends Model
{
    use Auditable, BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return [
            'member_type' => MemberType::class,
            'status' => MembershipStatus::class,
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditPrefix(): string
    {
        return 'organization_member';
    }
}
