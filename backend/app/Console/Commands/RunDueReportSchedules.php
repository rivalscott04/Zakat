<?php

namespace App\Console\Commands;

use App\Services\ReportScheduleService;
use Illuminate\Console\Command;

/** PRD 19W §19 — laporan terjadwal dijalankan penjadwal, bukan oleh permintaan pengguna. */
class RunDueReportSchedules extends Command
{
    protected $signature = 'zakat:run-due-report-schedules';

    protected $description = 'Jalankan laporan terjadwal yang sudah tiba waktunya';

    public function handle(ReportScheduleService $schedules): int
    {
        $this->info($schedules->runDue().' jadwal laporan dijalankan.');

        return self::SUCCESS;
    }
}
