<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['activity_id', 'mustahik_id', 'enrollment_id', 'attendance_status', 'participation_status', 'notes'])]
class ProgramActivityParticipant extends Model
{
    use HasUlids;
}
