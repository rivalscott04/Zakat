<?php

namespace App\Notifications\Contracts;

use App\Models\Notification;
use App\Models\NotificationDelivery;

/**
 * PRD 16B §4 — abstraksi channel.
 *
 * Menambah channel baru (WhatsApp, SMS, push) cukup dengan satu kelas yang
 * memenuhi kontrak ini lalu didaftarkan di NotificationChannelManager.
 */
interface NotificationChannelDriver
{
    /** Alamat tujuan pada channel ini, null bila recipient tidak dapat dijangkau. */
    public function resolveAddress(Notification $notification): ?string;

    /**
     * Kirim satu delivery. Lempar exception bila gagal supaya retry berjalan.
     *
     * @return array{provider: ?string, provider_reference: ?string, delivered: bool}
     */
    public function send(NotificationDelivery $delivery): array;
}
