<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationDeliveryStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PRD 16E §10. Tidak memakai BelongsToOrganization karena isolasinya ikut
 * notification induk lewat relasi.
 */
class NotificationDelivery extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'status' => NotificationDeliveryStatus::class,
            'attempt_count' => 'integer',
            'max_attempts' => 'integer',
            'last_attempt_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    /** PRD 16P §33 — retry berhenti pada maximum yang dikonfigurasi. */
    public function canRetry(): bool
    {
        return ! $this->status->isSuccessful()
            && $this->status !== NotificationDeliveryStatus::Cancelled
            && $this->attempt_count < $this->max_attempts;
    }
}
