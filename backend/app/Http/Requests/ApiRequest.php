<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * PRD 22 §6 dan CLAUDE.md §19 — seluruh input utama divalidasi lewat Form Request.
 *
 * Authorization tidak dilakukan di sini: permission dicek middleware `permission`
 * dan object-level access dicek Service Layer, supaya aturannya tidak tersebar.
 */
abstract class ApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** PRD 01 §14 — password policy tunggal untuk seluruh alur. */
    protected static function passwordRule(): Password
    {
        $rule = Password::min((int) config('zakat.password.min_length'));

        // PRD 01 §14 — penolakan password umum bersifat opsional. Dimatikan
        // secara default karena memerlukan panggilan ke layanan eksternal.
        return config('zakat.password.reject_compromised') ? $rule->uncompromised() : $rule;
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        return $this->only(['search', 'status', 'per_page', 'page', 'type', 'zakat_type_id', 'organization_id', 'muzaki_id', 'eligibility_status', 'calculation_date_from', 'calculation_date_to', 'created_by', 'source', 'date_from', 'date_to']);
    }
}
