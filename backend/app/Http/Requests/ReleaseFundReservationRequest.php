<?php

namespace App\Http\Requests;

class ReleaseFundReservationRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:1000']];
    }
}
