<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['collection_id', 'zakat_type_id', 'calculation_id', 'description', 'quantity', 'unit', 'expected_amount', 'paid_amount', 'remaining_amount', 'status'])]
class CollectionItem extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return ['quantity' => 'decimal:8', 'expected_amount' => 'decimal:2', 'paid_amount' => 'decimal:2', 'remaining_amount' => 'decimal:2'];
    }

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}
