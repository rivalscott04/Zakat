<?php

namespace App\Models;

use App\Enums\DuplicateReviewStatus;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MuzakiDuplicateReview extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['match_reasons' => 'array', 'status' => DuplicateReviewStatus::class, 'reviewed_at' => 'datetime'];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Muzaki::class, 'source_muzaki_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Muzaki::class, 'candidate_muzaki_id');
    }
}
