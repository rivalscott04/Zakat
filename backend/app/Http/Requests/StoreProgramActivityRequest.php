<?php

namespace App\Http\Requests;

class StoreProgramActivityRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['activity_code' => ['required', 'string', 'max:50'], 'name' => ['required', 'string', 'max:150'], 'description' => ['nullable', 'string'], 'activity_type' => ['required', 'in:training,distribution,mentoring,visit,monitoring,event,workshop,other'], 'start_date' => ['nullable', 'date'], 'end_date' => ['nullable', 'date', 'after_or_equal:start_date'], 'location' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', 'in:draft,active,completed,cancelled']];
    }
}
