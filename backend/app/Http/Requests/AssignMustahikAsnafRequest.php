<?php

namespace App\Http\Requests;

class AssignMustahikAsnafRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['asnaf_code' => ['required', 'in:fakir,miskin,amil,mualaf,riqab,gharim,fisabilillah,ibnusabil'], 'primary_asnaf' => ['boolean'], 'assessment_id' => ['nullable', 'ulid'], 'reason' => ['required', 'string', 'max:1000'], 'effective_from' => ['nullable', 'date']];
    }
}
