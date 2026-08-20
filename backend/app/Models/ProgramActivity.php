<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['program_id', 'activity_code', 'name', 'description', 'activity_type', 'start_date', 'end_date', 'location', 'status'])]
class ProgramActivity extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ProgramActivityParticipant::class, 'activity_id');
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}
