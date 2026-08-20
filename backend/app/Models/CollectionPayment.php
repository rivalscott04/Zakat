<?php

namespace App\Models;

use App\Enums\CollectionPaymentStatus;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['payment_reference', 'collection_id', 'status', 'payment_method', 'payment_instrument', 'amount', 'currency', 'payment_date', 'verified_at', 'verified_by', 'metadata'])]
class CollectionPayment extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return ['status' => CollectionPaymentStatus::class, 'amount' => 'decimal:2', 'payment_date' => 'datetime', 'verified_at' => 'datetime', 'metadata' => 'array'];
    }

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class, 'payment_id');
    }
}
