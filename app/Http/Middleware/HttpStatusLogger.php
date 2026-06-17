<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class HttpStatusLogger
{
    private const IMPORTANT_STATUSES = [
        300, 301, 302, 303, 307, 308,
        400, 401, 403, 404, 419, 422, 429,
        500, 502, 503, 504,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);
        $status = $response->getStatusCode();

        if (in_array($status, self::IMPORTANT_STATUSES, true) || $status >= 500) {
            Log::warning('IRIS HTTP status importante', [
                'status' => $status,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'route' => optional($request->route())->getName(),
                'user_id' => optional($request->user())->id,
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'referer' => $request->headers->get('referer'),
            ]);
        }

        return $response;
    }
}
