<?php

namespace App\Http\Requests;

class UpdateDistributionRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['description' => ['nullable', 'string'], 'scheduled_date' => ['nullable', 'date'], 'priority' => ['nullable', 'in:low,normal,high,urgent']];
    }
}
