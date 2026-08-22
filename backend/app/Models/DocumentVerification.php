<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['document_id', 'status', 'verification_note', 'verified_by', 'verified_at'])] class DocumentVerification extends Model
{
    use BelongsToOrganization,HasUlids;

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }
}
