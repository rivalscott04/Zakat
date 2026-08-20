<?php

namespace App\Http\Requests;

/** PRD 01 §9 — login dengan email atau username dan password. */
class LoginRequest extends ApiRequest
{
    protected function prepareForValidation(): void
    {
        // Kompatibilitas: payload lama mengirim "email"; payload baru memakai "login".
        if (! $this->filled('login') && $this->filled('email')) {
            $this->merge(['login' => $this->input('email')]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255', 'regex:/^([^\s@]+@[^\s@]+\.[^\s@]+|[a-zA-Z0-9_-]+)$/'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'login.required' => 'Email atau username wajib diisi.',
            'login.regex' => 'Format email atau username tidak valid.',
        ];
    }
}
