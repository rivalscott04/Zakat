<?php

namespace App\Http\Requests;

use App\Enums\NotificationChannel;
use Illuminate\Validation\Rule;

/** PRD 16J §22 dan §23. */
class StoreNotificationTemplateRequest extends ApiRequest
{
    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'template_code' => [$required, 'string', 'max:60', 'regex:/^[A-Za-z0-9]+$/'],
            'name' => [$required, 'string', 'max:150'],
            'channel' => [$required, Rule::enum(NotificationChannel::class)],
            'subject' => ['sometimes', 'nullable', 'string', 'max:255'],
            'content' => [$required, 'string', 'max:20000'],
            'locale' => ['sometimes', 'string', 'max:10'],
            'variables' => ['sometimes', 'nullable', 'array'],
            'variables.*' => ['string', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return ['template_code.regex' => 'Template code hanya boleh huruf dan angka, tanpa dash (PRD 16J §23).'];
    }
}
