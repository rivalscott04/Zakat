<?php

namespace App\Http\Requests;

use App\Enums\TransparencySnapshotType;
use Illuminate\Validation\Rule;

/** PRD 18D §6. */
class StoreTransparencySnapshotRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'snapshot_type' => ['required', Rule::enum(TransparencySnapshotType::class)],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ];
    }
}
