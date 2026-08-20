<?php

namespace App\Http\Requests;

/** PRD 02 §26 — perpindahan active organization. */
class SwitchOrganizationRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        // Membership tetap diverifikasi ulang di service, bukan di sini.
        return ['organization_id' => ['required', 'string', 'ulid']];
    }
}
