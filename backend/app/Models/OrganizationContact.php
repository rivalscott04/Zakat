<?php

namespace App\Models;

use App\Enums\ContactType;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** PRD 02 §23 — kontak organisasi. */
#[Fillable(['type', 'label', 'value', 'is_primary'])]
class OrganizationContact extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return [
            'type' => ContactType::class,
            'is_primary' => 'boolean',
        ];
    }
}
