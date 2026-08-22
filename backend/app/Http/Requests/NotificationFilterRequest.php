<?php

namespace App\Http\Requests;

use App\Enums\NotificationPriority;
use Illuminate\Validation\Rule;

/** PRD 16U §43. */
class NotificationFilterRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'unread' => ['sometimes', 'nullable', 'boolean'],
            'priority' => ['sometimes', 'nullable', Rule::enum(NotificationPriority::class)],
            'event_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.config('zakat.pagination.max_per_page')],
        ];
    }
}
