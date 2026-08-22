<?php

namespace App\Jobs;

use App\Models\ReportRun;
use App\Services\ReportRunService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** PRD 19V §57 — laporan besar dikerjakan di queue, bukan di request utama. */
class GenerateReportRun implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public readonly string $runId) {}

    public function handle(ReportRunService $runs): void
    {
        $run = ReportRun::query()->withoutGlobalScopes()->with('report')->find($this->runId);

        if ($run !== null) {
            $runs->generate($run);
        }
    }
}
