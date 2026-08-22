<?php

namespace App\Http\Requests;

/** PRD 15C — perubahan metadata. Status diubah lewat aksi tersendiri. */
class UpdateDocumentRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'document_name' => ['sometimes', 'required', 'string', 'max:255'],
            'category' => ['sometimes', 'nullable', 'string', 'max:50'],
            'visibility' => ['sometimes', 'in:PRIVATE,INTERNAL,PUBLIC'],
            'expires_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
