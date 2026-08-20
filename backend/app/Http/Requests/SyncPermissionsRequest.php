<?php

namespace App\Http\Requests;

/** PRD 01 §26 — penetapan permission pada sebuah role. */
class SyncPermissionsRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'permission_ids' => ['present', 'array'],
            'permission_ids.*' => ['string', 'ulid'],
        ];
    }
}
