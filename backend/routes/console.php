<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// F-14 — dijalankan terjadwal, bukan sebagai efek samping endpoint baca.
Schedule::command('zakat:expire-due-collections')->dailyAt('00:15');
