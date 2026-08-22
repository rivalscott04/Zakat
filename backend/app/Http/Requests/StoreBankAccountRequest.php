<?php

namespace App\Http\Requests;

/** PRD 14C §5 dan §8. Nomor rekening disimpan terenkripsi oleh service. */
class StoreBankAccountRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'bank_name' => ['required', 'string', 'max:120'],
            'account_name' => ['required', 'string', 'max:120'],
            'account_number' => ['required', 'string', 'max:40', 'regex:/^[0-9-]+$/'],
            'currency' => ['nullable', 'string', 'size:3', 'alpha'],
            'opening_balance' => ['nullable', 'numeric', 'gte:0', 'max:999999999999'],
        ];
    }

    public function messages(): array
    {
        return ['account_number.regex' => 'Nomor rekening hanya boleh berisi angka dan tanda hubung.'];
    }
}
