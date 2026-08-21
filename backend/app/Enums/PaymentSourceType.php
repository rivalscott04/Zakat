<?php

namespace App\Enums;

/**
 * PRD 13D §9 dan §10.
 *
 * Nilai menjelaskan sifat transaksi sumber, sedangkan `source_id` menunjuk ke
 * record milik modul sumber. Pemetaan ke tabel dilakukan PaymentSourceResolver
 * supaya modul ini tidak bergantung pada struktur internal modul lain.
 */
enum PaymentSourceType: string
{
    case Zakat = 'zakat';
    case Infaq = 'infaq';
    case Sedekah = 'sedekah';
    case Donation = 'donation';
    case Campaign = 'campaign';
    case Other = 'other';
}
