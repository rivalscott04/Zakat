<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MuzakiTag extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $guarded = [];

    public function assignments(): HasMany
    {
        return $this->hasMany(MuzakiTagAssignment::class, 'tag_id');
    }
}
