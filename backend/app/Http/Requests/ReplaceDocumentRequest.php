<?php

namespace App\Http\Requests;

use App\Services\DocumentService;

/** PRD 15P — penggantian berkas membuat versi baru. */
class ReplaceDocumentRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'file' => [
                'required', 'file',
                'max:'.(DocumentService::MAX_BYTES / 1024),
                'extensions:'.implode(',', array_keys(DocumentService::ALLOWED)),
                'mimetypes:'.implode(',', array_unique(array_merge(...array_values(DocumentService::ALLOWED)))),
            ],
            'change_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
