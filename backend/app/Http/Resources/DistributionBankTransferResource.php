<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PRD 12D §9 — nomor rekening hanya keluar dalam bentuk mask.
 *
 * PRD menyebut ada permission khusus untuk melihat nomor penuh, tetapi daftar
 * permission PRD 12AB §65 tidak mendefinisikan kodenya. Sampai kode itu
 * ditetapkan, nomor penuh tidak pernah dikirim lewat API.
 */
class DistributionBankTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bank_name' => $this->bank_name,
            'account_holder_name' => $this->account_holder_name,
            'account_number_masked' => $this->account_number_masked,
            'transfer_reference' => $this->transfer_reference,
            'transfer_amount' => $this->transfer_amount,
            'transfer_date' => $this->transfer_date?->toDateString(),
            'status' => $this->status->value,
            'failure_reason' => $this->failure_reason,
        ];
    }
}
