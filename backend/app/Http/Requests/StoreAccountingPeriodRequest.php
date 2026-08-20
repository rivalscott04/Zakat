<?php

namespace App\Http\Requests;

class StoreAccountingPeriodRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['period_code' => ['required', 'string', 'size:6'], 'name' => ['required', 'string'], 'start_date' => ['required', 'date'], 'end_date' => ['required', 'date', 'after_or_equal:start_date']];
    }
}
