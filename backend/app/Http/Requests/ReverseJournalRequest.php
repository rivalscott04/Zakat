<?php

namespace App\Http\Requests;

class ReverseJournalRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:1000'], 'journal_date' => ['nullable', 'date']];
    }
}
