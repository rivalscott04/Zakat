<?php

namespace App\Models;

use App\Enums\PaymentReconciliationResult;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** PRD 13P §31. */
class PaymentReconciliation extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'internal_amount' => 'decimal:2',
            'provider_amount' => 'decimal:2',
            'result' => PaymentReconciliationResult::class,
            'reconciled_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
