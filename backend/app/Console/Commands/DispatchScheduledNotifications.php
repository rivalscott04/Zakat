<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

/** PRD 16Q §35 — notification berjadwal masuk antrean ketika waktunya tiba. */
class DispatchScheduledNotifications extends Command
{
    protected $signature = 'zakat:dispatch-scheduled-notifications';

    protected $description = 'Masukkan notification berstatus scheduled ke antrean bila waktunya sudah tiba';

    public function handle(NotificationService $notifications): int
    {
        $this->info($notifications->dispatchScheduled().' notification dimasukkan ke antrean.');

        return self::SUCCESS;
    }
}
