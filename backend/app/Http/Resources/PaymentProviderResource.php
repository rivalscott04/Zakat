<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PRD 13T §40 — kredensial tidak pernah dikembalikan.
 *
 * Yang keluar hanya kunci konfigurasinya dan penanda apakah webhook secret
 * sudah diisi, cukup untuk kebutuhan UI tanpa membocorkan nilainya.
 */
class PaymentProviderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider_code' => $this->provider_code,
            'name' => $this->name,
            'driver' => $this->driver,
            'status' => $this->status->value,
            'sandbox_mode' => $this->sandbox_mode,
            'configured_keys' => $this->configuredKeys(),
            'webhook_secret_configured' => $this->hasWebhookSecret(),
            'webhook_url' => url("/api/v1/webhooks/payments/{$this->id}"),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
