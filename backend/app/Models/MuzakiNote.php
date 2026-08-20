<?php

namespace App\Models;

use App\Enums\NoteVisibility;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MuzakiNote extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['visibility' => NoteVisibility::class];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
