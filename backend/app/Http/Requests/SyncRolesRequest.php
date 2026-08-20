<?php

namespace App\Http\Requests;

/** PRD 01 §32 — penetapan role user pada organization aktif. */
class SyncRolesRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'role_ids' => ['present', 'array'],
            'role_ids.*' => ['string', 'ulid'],
        ];
    }
}
