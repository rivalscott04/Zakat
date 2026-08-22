<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// F-14 — dijalankan terjadwal, bukan sebagai efek samping endpoint baca.
Schedule::command('zakat:expire-due-collections')->dailyAt('00:15');

// PRD 13M §25 — payment pending yang lewat masa berlaku ditutup otomatis.
Schedule::command('zakat:expire-pending-payments')->everyFifteenMinutes();

// PRD 16Q §35 — notification berjadwal dilepas ke antrean oleh scheduler.
Schedule::command('zakat:dispatch-scheduled-notifications')->everyFiveMinutes();
