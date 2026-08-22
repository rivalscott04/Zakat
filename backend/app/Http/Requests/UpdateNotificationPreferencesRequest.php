<?php

namespace App\Http\Requests;

use App\Enums\NotificationChannel;
use Illuminate\Validation\Rule;

/** PRD 16U §46. */
class UpdateNotificationPreferencesRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'preferences' => ['required', 'array', 'min:1'],
            'preferences.*.event_name' => ['required', 'string', 'max:100'],
            'preferences.*.channel' => ['required', Rule::enum(NotificationChannel::class)],
            'preferences.*.enabled' => ['required', 'boolean'],
        ];
    }
}
