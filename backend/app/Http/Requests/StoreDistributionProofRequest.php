<?php

namespace App\Http\Requests;

use App\Enums\DistributionProofType;
use Illuminate\Validation\Rule;

/** PRD 12R §44. file_id akan mengacu ke modul Document Management (PRD 15). */
class StoreDistributionProofRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'proof_type' => ['required', Rule::enum(DistributionProofType::class)],
            'file_id' => ['nullable', 'string', 'ulid'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
