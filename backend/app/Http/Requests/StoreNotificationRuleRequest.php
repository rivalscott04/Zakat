<?php

namespace App\Http\Requests;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationRecipientStrategy;
use Illuminate\Validation\Rule;

/** PRD 16L §28 dan PRD 16M §29. */
class StoreNotificationRuleRequest extends ApiRequest
{
    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'event_name' => [$required, 'string', 'max:100'],
            'template_id' => ['sometimes', 'nullable', 'string', 'ulid'],
            'channels' => [$required, 'array', 'min:1'],
            'channels.*' => [Rule::enum(NotificationChannel::class)],
            'recipient_strategy' => [$required, Rule::enum(NotificationRecipientStrategy::class)],
            'recipient_config' => ['sometimes', 'nullable', 'array'],
            'recipient_config.user_ids' => ['sometimes', 'array'],
            'recipient_config.user_ids.*' => ['string', 'ulid'],
            'recipient_config.roles' => ['sometimes', 'array'],
            'recipient_config.roles.*' => ['string', 'max:50'],
            'recipient_config.permissions' => ['sometimes', 'array'],
            'recipient_config.permissions.*' => ['string', 'max:100'],
            'priority' => ['sometimes', Rule::enum(NotificationPriority::class)],
        ];
    }
}
