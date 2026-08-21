<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Enums\PaymentSourceType;
use App\Support\OrganizationContext;
use Illuminate\Validation\Rule;

/**
 * PRD 13G §15.
 *
 * `amount` tetap diminta untuk pembayaran sebagian, tetapi diverifikasi ulang
 * terhadap sisa tagihan transaksi sumber di service.
 */
class StorePaymentRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'provider_id' => [
                'required', 'string', 'ulid',
                Rule::exists('payment_providers', 'id')->where('organization_id', OrganizationContext::id()),
            ],
            'source_type' => ['required', Rule::enum(PaymentSourceType::class)],
            'source_id' => [
                'required', 'string', 'ulid',
                Rule::exists('collections', 'id')->where('organization_id', OrganizationContext::id()),
            ],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'payer_name' => ['nullable', 'string', 'max:255'],
            'payer_email' => ['nullable', 'email', 'max:255'],
            'payer_phone' => ['nullable', 'string', 'max:30'],
            'internal_reference' => ['nullable', 'string', 'max:120'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
