<?php

namespace App\Http\Requests;

/** PRD 18Z §16 — pencabutan publikasi wajib disertai alasan. */
class RevokeTransparencySnapshotRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:10', 'max:1000']];
    }
}
