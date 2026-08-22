<?php

namespace App\Http\Resources;

use App\Models\ReportParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** PRD 19R §43. */
class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_number' => $this->report_number,
            'report_code' => $this->report_code,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category->value,
            'report_type' => $this->report_type,
            'visibility' => $this->visibility->value,
            'status' => $this->status->value,
            'is_system' => $this->is_system,
            'parameters' => $this->whenLoaded('parameters', fn () => $this->parameters->map(fn (ReportParameter $parameter) => [
                'parameter_code' => $parameter->parameter_code,
                'label' => $parameter->label,
                'type' => $parameter->type->value,
                'required' => $parameter->required,
                'default_value' => $parameter->default_value,
                'options_source' => $parameter->options_source,
            ])),
        ];
    }
}
