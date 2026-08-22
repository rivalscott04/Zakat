<?php

namespace App\Models;

use App\Enums\NotificationPriority;
use App\Enums\NotificationRecipientType;
use App\Enums\NotificationStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasBusinessNumber;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PRD 16C §5.
 *
 * Status, waktu kirim, dan status baca tidak fillable: hanya Service Layer dan
 * driver channel yang boleh menentukannya.
 */
#[Fillable(['recipient_type', 'recipient_id', 'title', 'message', 'data', 'priority', 'scheduled_at'])]
class Notification extends Model
{
    use BelongsToOrganization, HasBusinessNumber, HasUlids;

    public static function businessCode(): string
    {
        return 'NTF';
    }

    public function businessNumberColumn(): string
    {
        return 'notification_number';
    }

    protected function casts(): array
    {
        return [
            'recipient_type' => NotificationRecipientType::class,
            'priority' => NotificationPriority::class,
            'status' => NotificationStatus::class,
            'data' => 'array',
            'read_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(NotificationRule::class, 'rule_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(NotificationBatch::class, 'batch_id');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
