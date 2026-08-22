<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * PRD 16S §39. Bukan BelongsToOrganization: preference dibaca saat mengirim
 * notification untuk user lain, di luar context organisasi pemicu.
 */
class NotificationPreference extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'enabled' => 'boolean',
        ];
    }
}
