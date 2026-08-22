<?php

namespace App\Http\Requests;

use App\Enums\EntityStatus;
use Illuminate\Validation\Rule;

/** PRD 16I §20. */
class StoreNotificationWebhookRequest extends ApiRequest
{
    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:150'],
            // https wajib: payload notifikasi memuat data organisasi.
            'url' => [$required, 'url', 'starts_with:https://', 'max:500'],
            'events' => ['sometimes', 'nullable', 'array'],
            'events.*' => ['string', 'max:100'],
            'status' => ['sometimes', Rule::enum(EntityStatus::class)],
        ];
    }
}
