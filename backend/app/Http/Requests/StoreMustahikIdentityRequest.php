<?php

namespace App\Http\Requests;

class StoreMustahikIdentityRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['identity_type' => ['required', 'in:nik,passport,family_card,student_id,local_id,other'], 'identity_number' => ['required', 'string', 'max:100'], 'identity_name' => ['nullable', 'string', 'max:150']];
    }
}
