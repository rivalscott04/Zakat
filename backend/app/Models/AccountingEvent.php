<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['event_type', 'source_type', 'source_id', 'reference_number', 'event_date', 'payload', 'status', 'processed_at', 'journal_entry_id'])]
class AccountingEvent extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return ['event_date' => 'date', 'payload' => 'array', 'processed_at' => 'datetime'];
    }

    public function journal()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }
}
