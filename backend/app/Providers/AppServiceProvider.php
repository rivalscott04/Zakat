<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Date;
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
    }
}
