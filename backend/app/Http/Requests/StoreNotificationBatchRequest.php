<?php

namespace App\Http\Requests;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use Illuminate\Validation\Rule;

/** PRD 16R §36. */
class StoreNotificationBatchRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => [Rule::enum(NotificationChannel::class)],
            'recipient_ids' => ['required', 'array', 'min:1', 'max:1000'],
            'recipient_ids.*' => ['string', 'ulid'],
            'priority' => ['sometimes', Rule::enum(NotificationPriority::class)],
        ];
    }
}
