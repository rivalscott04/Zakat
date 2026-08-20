<?php

namespace App\Http\Requests;

class StoreProgramEligibilityRuleRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['rule_code' => ['required', 'string', 'max:60', 'regex:/^[A-Z0-9_]+$/'], 'rule_type' => ['required', 'in:mustahik,assessment,asnaf,demographic,geographic,socioeconomic,custom'], 'field' => ['required', 'string', 'max:100'], 'operator' => ['required', 'in:equals,not_equals,greater_than,less_than,greater_than_or_equal,less_than_or_equal,in,not_in,contains,exists'], 'value' => ['required'], 'weight' => ['nullable', 'numeric', 'gte:0'], 'required' => ['boolean'], 'sort_order' => ['nullable', 'integer', 'min:0']];
    }
}
