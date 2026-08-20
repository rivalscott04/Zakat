<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** PRD 00 §10 — registry business code. */
class CodeRegistry extends Model
{
    use HasUlids;

    protected $table = 'code_registry';

    protected $fillable = ['code', 'name', 'entity_type', 'module', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
