<?php

namespace App\Http\Requests;

use App\Support\SettingRegistry;

/** PRD 20 — hanya key terdaftar yang boleh ditulis, dengan rule masing-masing. */
class UpdateSettingsRequest extends ApiRequest
{
    public function rules(): array
    {
        $rules = [
            'scope' => ['required', 'in:'.SettingRegistry::GLOBAL.','.SettingRegistry::ORGANIZATION],
            'values' => ['required', 'array', 'min:1'],
        ];

        foreach (SettingRegistry::KEYS as $key => $meta) {
            // Titik pada nama key adalah bagian dari key, bukan penanda nested,
            // jadi harus di-escape agar `validated()` tetap mengembalikan `values`.
            $rules['values.'.str_replace('.', '\\.', $key)] = $meta['rules'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return ['values.*' => 'Nilai setting :attribute tidak valid.'];
    }

    protected function prepareForValidation(): void
    {
        $values = $this->input('values');

        if (is_array($values)) {
            $unknown = array_diff(array_keys($values), array_keys(SettingRegistry::KEYS));

            // Key asing dibuang lebih awal supaya tidak lolos sebagai array bebas.
            $this->merge(['values' => array_diff_key($values, array_flip($unknown))]);
        }
    }
}
