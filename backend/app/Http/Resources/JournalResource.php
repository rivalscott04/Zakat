<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'journal_number' => $this->journal_number, 'journal_date' => $this->journal_date?->toDateString(), 'accounting_period_id' => $this->accounting_period_id, 'journal_type' => $this->journal_type, 'reference_number' => $this->reference_number, 'description' => $this->description, 'status' => $this->status, 'reversal_of_id' => $this->reversal_of_id, 'lines' => $this->whenLoaded('lines')];
    }
}
