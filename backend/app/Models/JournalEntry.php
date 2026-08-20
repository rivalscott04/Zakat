<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['journal_number', 'journal_date', 'accounting_period_id', 'journal_type', 'source_type', 'source_id', 'reference_number', 'description', 'status', 'reversal_of_id', 'created_by', 'posted_by', 'posted_at'])]
class JournalEntry extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return ['journal_date' => 'date', 'posted_at' => 'datetime'];
    }

    public function lines()
    {
        return $this->hasMany(JournalLine::class);
    }

    public function period()
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }
}
