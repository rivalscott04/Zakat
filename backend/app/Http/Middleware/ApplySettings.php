<?php

namespace App\Http\Middleware;

use App\Services\SettingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PRD 20 — memasang System Setting global ke config sebelum request diproses,
 * termasuk pada route publik seperti login yang membaca policy password dan
 * ambang penguncian akun. Lapisan organisasi ditambahkan kemudian oleh
 * ResolveOrganizationContext.
 */
class ApplySettings
{
    public function __construct(private readonly SettingService $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->settings->apply(null);

        return $next($request);
    }
}
