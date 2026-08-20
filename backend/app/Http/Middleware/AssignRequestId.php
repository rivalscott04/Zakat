<?php

namespace App\Http\Middleware;

use App\Support\RequestId;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/** PRD 00 §19 dan §35 — request id untuk tracing dan konteks logging. */
class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->headers->get('X-Request-Id');

        // Header dari client tidak dipercaya isinya, hanya dipakai bila berbentuk ULID.
        if (! is_string($id) || ! Str::isUlid($id)) {
            $id = (string) Str::ulid();
        }

        RequestId::set($id);
        $request->attributes->set('request_id', $id);

        Log::shareContext([
            'request_id' => $id,
            'user_id' => $request->user()?->getKey(),
        ]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $id);

        return $response;
    }
}
