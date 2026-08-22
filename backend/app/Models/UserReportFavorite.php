<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** PRD 19P §41. */
class UserReportFavorite extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    protected $guarded = [];
}
