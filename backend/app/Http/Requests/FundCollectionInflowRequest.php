<?php

namespace App\Http\Requests;

class FundCollectionInflowRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['fund_id' => ['required', 'ulid'], 'collection_id' => ['required', 'ulid']];
    }
}
