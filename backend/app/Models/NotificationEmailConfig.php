<?php

namespace App\Models;

use App\Enums\EntityStatus;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** PRD 16H §17 dan §18. */
#[Fillable(['driver', 'host', 'port', 'from_name', 'from_email', 'encryption'])]
class NotificationEmailConfig extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $hidden = ['username_encrypted', 'password_encrypted'];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'status' => EntityStatus::class,
            'username_encrypted' => 'encrypted',
            'password_encrypted' => 'encrypted',
        ];
    }
}
