<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['period_code', 'name', 'start_date', 'end_date', 'status', 'closed_at', 'closed_by'])]
class AccountingPeriod extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'closed_at' => 'datetime'];
    }

    public function journals()
    {
        return $this->hasMany(JournalEntry::class);
    }
}
