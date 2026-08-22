<?php

namespace App\Models;

use App\Enums\NotificationPriority;
use App\Enums\NotificationRecipientStrategy;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** PRD 16L §28. */
#[Fillable(['event_name', 'template_id', 'channels', 'recipient_strategy', 'recipient_config', 'priority'])]
class NotificationRule extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'recipient_config' => 'array',
            'recipient_strategy' => NotificationRecipientStrategy::class,
            'priority' => NotificationPriority::class,
            'enabled' => 'boolean',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }
}
