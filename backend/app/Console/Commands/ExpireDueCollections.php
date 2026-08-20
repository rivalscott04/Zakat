<?php

namespace App\Console\Commands;

use App\Enums\CollectionStatus;
use App\Models\Collection;
use Illuminate\Console\Command;

/**
 * F-14 — penandaan collection kedaluwarsa dipindahkan dari endpoint baca.
 *
 * Sebelumnya `UPDATE` massal ini berjalan di dalam `GET /collections` dan
 * `GET /collections/summary`, sehingga endpoint baca punya efek samping tulis
 * yang biayanya bertambah seiring data.
 */
class ExpireDueCollections extends Command
{
    protected $signature = 'zakat:expire-due-collections';

    protected $description = 'Tandai collection yang melewati jatuh tempo dan masih punya sisa tagihan sebagai expired';

    public function handle(): int
    {
        $affected = Collection::query()
            // Lintas organisasi: perintah ini dijalankan penjadwal, bukan user.
            ->acrossOrganizations()
            ->whereIn('status', [CollectionStatus::Draft, CollectionStatus::Pending, CollectionStatus::PartiallyPaid])
            ->whereDate('due_date', '<', today())
            ->where('remaining_amount', '>', 0)
            ->update(['status' => CollectionStatus::Expired, 'updated_at' => now()]);

        $this->info("{$affected} collection ditandai expired.");

        return self::SUCCESS;
    }
}
