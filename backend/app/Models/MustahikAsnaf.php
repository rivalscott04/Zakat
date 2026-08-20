<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['mustahik_id', 'asnaf_code', 'primary_asnaf', 'assessment_id', 'reason', 'status', 'effective_from', 'effective_until', 'assigned_by'])]
class MustahikAsnaf extends Model
{
    protected $table = 'mustahik_asnaf';

    use HasUlids;

    protected function casts(): array
    {
        return ['primary_asnaf' => 'boolean', 'effective_from' => 'date', 'effective_until' => 'date'];
    }

    public function mustahik()
    {
        return $this->belongsTo(Mustahik::class);
    }
}
