<?php

namespace App\Http\Requests;

use App\Enums\DistributionConfirmationMethod;
use Illuminate\Validation\Rule;

/** PRD 12S §46. */
class ConfirmDistributionRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'confirmation_method' => ['required', Rule::enum(DistributionConfirmationMethod::class)],
            'confirmed_at' => ['nullable', 'date'],
            'confirmation_data' => ['nullable', 'array'],
        ];
    }
}
