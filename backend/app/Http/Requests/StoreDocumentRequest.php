<?php

namespace App\Http\Requests;

use App\Services\DocumentService;

/**
 * PRD 15I dan §19.
 *
 * Validasi berlapis: aturan Laravel menyaring lebih dulu, lalu DocumentService
 * memeriksa kecocokan isi berkas dengan ekstensinya.
 */
class StoreDocumentRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'document_name' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'string', 'max:30'],
            'category' => ['nullable', 'string', 'max:50'],
            'visibility' => ['nullable', 'in:PRIVATE,INTERNAL,PUBLIC'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'file' => [
                'required', 'file',
                'max:'.(DocumentService::MAX_BYTES / 1024),
                'extensions:'.implode(',', array_keys(DocumentService::ALLOWED)),
                'mimetypes:'.implode(',', array_unique(array_merge(...array_values(DocumentService::ALLOWED)))),
            ],
        ];
    }
}
