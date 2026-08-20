<?php

namespace App\Http\Requests;

class StoreDistributionRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['mustahik_id' => ['required', 'string', 'exists:mustahiks,id'], 'fund_id' => ['required', 'string', 'exists:funds,id'], 'program_id' => ['nullable', 'string', 'exists:programs,id'], 'program_enrollment_id' => ['nullable', 'string', 'exists:program_enrollments,id'], 'assessment_id' => ['nullable', 'string', 'exists:assessments,id'], 'distribution_type' => ['required', 'in:cash,bank_transfer,goods,service,voucher,scholarship,business_capital,emergency,other'], 'source_type' => ['nullable', 'in:program,direct,emergency,campaign,other'], 'requested_amount' => ['required', 'numeric', 'gt:0'], 'currency' => ['nullable', 'string', 'size:3'], 'priority' => ['nullable', 'in:low,normal,high,urgent'], 'description' => ['nullable', 'string'], 'items' => ['nullable', 'array'], 'items.*.item_code' => ['required_with:items', 'string'], 'items.*.item_name' => ['required_with:items', 'string'], 'items.*.quantity' => ['nullable', 'numeric', 'gt:0'], 'items.*.unit_value' => ['nullable', 'numeric', 'gte:0']];
    }
}
