<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['category_code', 'name', 'description', 'parent_id', 'status', 'sort_order'])]
class ProgramCategory extends Model
{
    use BelongsToOrganization, HasUlids;
}
