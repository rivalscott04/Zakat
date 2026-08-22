<?php

namespace App\Http\Requests;

use App\Support\OrganizationContext;
use Illuminate\Validation\Rule;

/**
 * PRD 14F §16 dan §18.
 *
 * CLAUDE.md §37 — berkas unggahan divalidasi ekstensi, MIME, dan ukuran.
 * Mengandalkan ekstensi saja tidak cukup karena mudah dipalsukan.
 */
class ImportBankStatementRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'bank_account_id' => [
                'required', 'string', 'ulid',
                Rule::exists('bank_accounts', 'id')->where('organization_id', OrganizationContext::id()),
            ],
            'file' => [
                'required', 'file', 'max:10240',
                'mimetypes:text/csv,text/plain,application/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'extensions:csv,xlsx',
            ],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'opening_balance' => ['nullable', 'numeric', 'max:999999999999'],
            'closing_balance' => ['nullable', 'numeric', 'max:999999999999'],
            'date_column' => ['nullable', 'string', 'max:60'],
            'description_column' => ['nullable', 'string', 'max:60'],
            'debit_column' => ['nullable', 'string', 'max:60'],
            'credit_column' => ['nullable', 'string', 'max:60'],
            'balance_column' => ['nullable', 'string', 'max:60'],
            'reference_column' => ['nullable', 'string', 'max:60'],
        ];
    }
}
