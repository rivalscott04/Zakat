<?php

use App\Http\Controllers\Api\PublicTransparencyController;
use Illuminate\Support\Facades\Route;

/*
 | PRD 18V §39 — API transparansi publik, tanpa autentikasi.
 |
 | PRD 18Z §18 mewajibkan rate limit. Isinya juga di-cache di service layer
 | (PRD 18Z §19) supaya lalu lintas publik tidak memukul database transaksi.
 */

Route::middleware('throttle:public')->prefix('transparency')->group(function () {
    Route::get('/verify/{reference}', [PublicTransparencyController::class, 'verify']);
    Route::get('/{organization}', [PublicTransparencyController::class, 'dashboard']);
    Route::get('/{organization}/summary', [PublicTransparencyController::class, 'summary']);
    Route::get('/{organization}/programs', [PublicTransparencyController::class, 'programs']);
    Route::get('/{organization}/reports', [PublicTransparencyController::class, 'reports']);
});
