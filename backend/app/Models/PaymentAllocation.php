<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['payment_id', 'collection_id', 'collection_item_id', 'allocated_amount', 'currency'])]
class PaymentAllocation extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return ['allocated_amount' => 'decimal:2'];
    }

    public function payment()
    {
        return $this->belongsTo(CollectionPayment::class, 'payment_id');
    }

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }

    public function item()
    {
        return $this->belongsTo(CollectionItem::class, 'collection_item_id');
    }
}
