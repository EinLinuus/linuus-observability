<?php

declare(strict_types=1);

namespace LinuusObservability\LinuUsObservability\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Str;
use LinuusObservability\LinuUsObservability\LinuUsObservability;
use Symfony\Component\HttpFoundation\Response;

class RecordHttpRequest
{
    public function __construct(
        private readonly LinuUsObservability $observability,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);
        $requestId = $this->requestId($request);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        $route = $request->route();

        $this->observability->record([
            'type' => 'http.request',
            'timestamp' => now()->toISOString(),
            'message' => 'http request completed',
            'level' => 'info',
            'request_id' => $requestId,
            'http.request.method' => $request->method(),
            'url.path' => $this->normalizePath($request->path()),
            'http.route' => $route instanceof IlluminateRoute ? $this->normalizePath($route->uri()) : null,
            'http.response.status_code' => $response->getStatusCode(),
            'duration_ms' => round((hrtime(true) - $startedAt) / 1_000_000, 2),
            'client.address' => $request->ip(),
            'user.id' => $request->user()?->getAuthIdentifier(),
        ]);

        return $response;
    }

    private function requestId(Request $request): string
    {
        $incomingRequestId = trim((string) $request->headers->get('X-Request-Id'));

        return $incomingRequestId !== '' ? $incomingRequestId : (string) Str::uuid();
    }

    private function normalizePath(string $path): string
    {
        return '/'.ltrim($path, '/');
    }
}
