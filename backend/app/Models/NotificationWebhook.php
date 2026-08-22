<?php

namespace App\Models;

use App\Enums\EntityStatus;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** PRD 16I §20. Secret disimpan terenkripsi dan tidak pernah dikembalikan penuh. */
#[Fillable(['name', 'url', 'events'])]
class NotificationWebhook extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $hidden = ['secret_encrypted'];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'status' => EntityStatus::class,
            'secret_encrypted' => 'encrypted',
        ];
    }
}
