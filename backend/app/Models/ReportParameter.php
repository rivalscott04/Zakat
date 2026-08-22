<?php

namespace App\Models;

use App\Enums\ReportParameterType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** PRD 19F §20. */
class ReportParameter extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => ReportParameterType::class,
            'required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
