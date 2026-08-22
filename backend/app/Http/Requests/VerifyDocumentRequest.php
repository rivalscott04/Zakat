<?php

namespace App\Http\Requests;

/** PRD 15Q — verifikasi dokumen. */
class VerifyDocumentRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['note' => ['nullable', 'string', 'max:500']];
    }
}
