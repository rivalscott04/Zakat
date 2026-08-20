<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // PRD 00 §12 — uang selalu NUMERIC(20,2). Macro ini menutup pintu
        // penggunaan float/double di migration modul keuangan berikutnya.
        Blueprint::macro('money', function (string $column, bool $nullable = false) {
            /** @var Blueprint $this */
            $definition = $this->decimal($column, 20, 2);

            return $nullable ? $definition->nullable() : $definition->default(0);
        });

        // CLAUDE.md §22 dan §47 — lazy loading, atribut yang tidak ada, dan
        // mass assignment diam-diam harus gagal keras saat development.
        Model::shouldBeStrict(! $this->app->isProduction());

        // PRD 00 §14 — penyimpanan selalu UTC, serialisasi selalu ISO 8601.
        Date::useDefault();

        // CLAUDE.md §36 — batasi endpoint yang rawan disalahgunakan. Kunci per
        // user supaya beberapa amil dalam satu kantor tidak saling menghabiskan
        // jatah lewat satu alamat IP.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->getKey() ?: $request->ip()));

        // Operasi yang menggerakkan uang atau menghasilkan dokumen berat.
        RateLimiter::for('financial', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->getKey() ?: $request->ip()));

        // PRD 01 §16 dan §45 — tautan reset mengarah ke SPA, bukan ke API.
        ResetPassword::createUrlUsing(fn ($notifiable, string $token) => rtrim((string) config('app.frontend_url'), '/')
            .'/auth/reset-password?token='.$token
            .'&email='.urlencode($notifiable->getEmailForPasswordReset()));
    }
}
