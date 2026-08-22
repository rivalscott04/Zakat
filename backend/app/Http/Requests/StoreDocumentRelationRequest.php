<?php

namespace App\Http\Requests;

/** PRD 15H — kaitan dokumen ke entitas modul lain. */
class StoreDocumentRelationRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'entity_type' => ['required', 'string', 'max:40'],
            'entity_id' => ['required', 'string', 'ulid'],
            'relation_type' => ['nullable', 'string', 'max:30'],
        ];
    }
}
