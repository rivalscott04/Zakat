<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Console\Command;

/**
 * PRD 13M §25 — payment pending yang melewati masa berlaku menjadi expired.
 *
 * Dijalankan penjadwal, bukan sebagai efek samping endpoint baca.
 */
class ExpirePendingPayments extends Command
{
    protected $signature = 'zakat:expire-pending-payments';

    protected $description = 'Tandai payment pending yang sudah melewati expires_at sebagai expired';

    public function handle(PaymentService $payments): int
    {
        $expired = 0;

        Payment::query()
            ->acrossOrganizations()
            ->where('status', PaymentStatus::Pending)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->each(function (Payment $payment) use ($payments, &$expired) {
                $payments->expire($payment);
                $expired++;
            });

        $this->info("{$expired} payment ditandai expired.");

        return self::SUCCESS;
    }
}
