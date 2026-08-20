<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['full_name', 'title_prefix', 'title_suffix', 'gender', 'birth_date', 'nationality', 'occupation', 'education_level'])]
class MuzakiIndividualProfile extends Model
{
    use Auditable, HasUlids;

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    public function auditPrefix(): string
    {
        return 'muzaki_individual_profile';
    }
}
