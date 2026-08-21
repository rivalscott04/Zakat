<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PaymentProvider;
use App\Services\PaymentWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PRD 13H §17 — endpoint webhook provider.
 *
 * Tanpa autentikasi user; kepercayaan sepenuhnya bertumpu pada tanda tangan.
 *
 * Provider ditunjuk lewat ULID, bukan provider_code, karena provider_code hanya
 * unik dalam satu organisasi (PRD 13B §5) sehingga tidak cukup mengidentifikasi
 * provider pada endpoint yang tidak punya organization context.
 */
class PaymentWebhookController extends Controller
{
    public function __construct(private readonly PaymentWebhookService $webhooks) {}

    public function __invoke(Request $request, string $providerId): JsonResponse
    {
        $provider = PaymentProvider::query()->acrossOrganizations()->find($providerId);

        // Jawaban seragam untuk provider tidak dikenal maupun tidak aktif, supaya
        // endpoint ini tidak bisa dipakai memetakan provider yang ada.
        if ($provider === null || ! $provider->isActive()) {
            return new JsonResponse(['status' => 'ignored'], 202);
        }

        $result = $this->webhooks->handle($provider, $request);

        // Provider umumnya menganggap non-2xx sebagai kegagalan dan mengirim ulang.
        // Penolakan karena tanda tangan tetap 202 agar tidak memicu banjir kiriman
        // ulang, dan jejaknya sudah tercatat pada payment_webhooks.
        return new JsonResponse($result, 202);
    }
}
