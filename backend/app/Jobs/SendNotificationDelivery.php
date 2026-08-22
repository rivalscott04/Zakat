<?php

namespace App\Jobs;

use App\Models\NotificationDelivery;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;

/**
 * PRD 16O §32 dan PRD 16P §33 — pengiriman channel eksternal berjalan di queue
 * dengan retry bertingkat. Batas percobaan yang menentukan status akhir ada di
 * kolom max_attempts milik delivery, bukan di sini.
 */
class SendNotificationDelivery implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(public readonly string $deliveryId) {}

    public function handle(NotificationService $notifications): void
    {
        $delivery = NotificationDelivery::query()->with('notification')->find($this->deliveryId);

        if ($delivery === null || $delivery->status->isFinal()) {
            return;
        }

        $delivery = $notifications->deliver($delivery);

        // Kegagalan yang masih punya sisa percobaan dilempar supaya queue
        // menjadwalkan ulang sesuai backoff; yang sudah habis berhenti di sini.
        if ($delivery->canRetry()) {
            throw new RuntimeException($delivery->error_message ?? 'Pengiriman notifikasi gagal.');
        }
    }
}
