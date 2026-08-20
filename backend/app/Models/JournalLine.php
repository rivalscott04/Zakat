<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['journal_entry_id', 'line_number', 'account_id', 'description', 'debit_amount', 'credit_amount', 'currency'])]
class JournalLine extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return ['debit_amount' => 'decimal:2', 'credit_amount' => 'decimal:2'];
    }

    public function journal()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}
