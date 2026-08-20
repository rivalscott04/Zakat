<?php

namespace App\Models;

use App\Enums\CollectionSource;
use App\Enums\CollectionStatus;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['collection_number', 'muzaki_id', 'calculation_id', 'zakat_type_id', 'zakat_rule_id', 'collection_date', 'due_date', 'status', 'currency', 'expected_amount', 'paid_amount', 'remaining_amount', 'payment_count', 'source', 'overpayment_status', 'source_snapshot', 'notes', 'cancellation_reason'])]
class Collection extends Model
{
    use BelongsToOrganization, HasUlids, SoftDeletes;

    protected function casts(): array
    {
        return ['status' => CollectionStatus::class, 'source' => CollectionSource::class, 'collection_date' => 'date', 'due_date' => 'date', 'expected_amount' => 'decimal:8', 'paid_amount' => 'decimal:8', 'remaining_amount' => 'decimal:8', 'source_snapshot' => 'array'];
    }

    public function muzaki(): BelongsTo
    {
        return $this->belongsTo(Muzaki::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ZakatType::class, 'zakat_type_id');
    }

    public function calculation(): BelongsTo
    {
        return $this->belongsTo(ZakatCalculation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CollectionItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CollectionPayment::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}
