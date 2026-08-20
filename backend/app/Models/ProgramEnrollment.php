<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['program_id', 'mustahik_id', 'enrollment_number', 'eligibility_result', 'assessment_id', 'enrolled_at', 'enrolled_by', 'approved_by', 'approved_at', 'status', 'notes'])]
class ProgramEnrollment extends Model
{
    use BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return ['enrolled_at' => 'datetime', 'approved_at' => 'datetime'];
    }

    public function mustahik()
    {
        return $this->belongsTo(Mustahik::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}
