<?php

namespace App\Http\Requests;

use App\Enums\DistributionType;
use App\Support\OrganizationContext;
use Illuminate\Validation\Rule;

/** PRD 12P §39. */
class StoreDistributionBatchRequest extends ApiRequest
{
    public function rules(): array
    {
        $organizationId = OrganizationContext::id();
        $scoped = fn (string $table) => Rule::exists($table, 'id')->where('organization_id', $organizationId);

        return [
            'name' => ['required', 'string', 'max:255'],
            'fund_id' => ['required', 'string', 'ulid', $scoped('funds')],
            'program_id' => ['nullable', 'string', 'ulid', $scoped('programs')],
            'distribution_type' => ['required', Rule::enum(DistributionType::class)],
        ];
    }
}
