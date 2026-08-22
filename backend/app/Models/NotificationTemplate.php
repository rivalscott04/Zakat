<?php

namespace App\Models;

use App\Enums\EntityStatus;
use App\Enums\NotificationChannel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** PRD 16J §22. */
#[Fillable(['template_code', 'name', 'channel', 'subject', 'content', 'locale', 'variables'])]
class NotificationTemplate extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'status' => EntityStatus::class,
            'variables' => 'array',
        ];
    }
}
