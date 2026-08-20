<?php

namespace App\Http\Requests;

use App\Support\OrganizationContext;
use Illuminate\Validation\Rule;

/** PRD 12Q §42. */
class StoreBatchBeneficiaryRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'mustahik_id' => [
                'required', 'string', 'ulid',
                Rule::exists('mustahiks', 'id')->where('organization_id', OrganizationContext::id()),
            ],
            'approved_amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
        ];
    }
}
