<?php

namespace App\Http\Requests;

class StoreProgramParticipantRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['mustahik_id' => ['required', 'string', 'exists:mustahiks,id'], 'enrollment_id' => ['nullable', 'string', 'exists:program_enrollments,id'], 'attendance_status' => ['nullable', 'in:registered,attended,absent,excused'], 'participation_status' => ['nullable', 'string', 'max:20'], 'notes' => ['nullable', 'string']];
    }
}
