<?php

namespace App\Http\Requests;

class CompleteDistributionRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['amount' => ['nullable', 'numeric', 'gt:0'], 'distribution_date' => ['nullable', 'date']];
    }
}
