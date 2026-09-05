<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->logPath = observabilityTestLogPath('agent-events.jsonl');
    $this->statePath = observabilityAgentStatePath();

    removeFiles([$this->logPath, $this->statePath]);

    config()->set('observability.log_path', $this->logPath);
    config()->set('observability.endpoint', 'https://example.test/ingest/v1/logs');
    config()->set('observability.token', 'secret-token');
    config()->set('observability.agent.batch_size', 100);
    config()->set('observability.agent.flush_interval_seconds', 0);
    config()->set('observability.agent.retry_sleep_seconds', 0);
    config()->set('observability.agent.max_line_bytes', 262144);
});

afterEach(function (): void {
    removeFiles([$this->logPath, $this->statePath]);
});

it('reads log lines and sends them as NDJSON', function () {
    file_put_contents(
        $this->logPath,
        <<<'NDJSON'
{"type":"http.request","message":"http request completed"}
{"type":"audit.event","message":"user.role_changed"}

NDJSON
    );

    Http::fake([
        'https://example.test/ingest/v1/logs' => Http::response('', 202),
    ]);

    $this->artisan('observability:agent --once')->assertSuccessful();

    Http::assertSentCount(1);
    Http::assertSent(static function (Request $request): bool {
        return $request->url() === 'https://example.test/ingest/v1/logs'
            && $request->hasHeader('Authorization', 'Bearer secret-token')
            && $request->hasHeader('Content-Type', 'application/x-ndjson')
            && str_contains((string) $request->body(), '{"type":"http.request","message":"http request completed"}')
            && str_contains((string) $request->body(), '{"type":"audit.event","message":"user.role_changed"}');
    });
});

it('does not crash when endpoint returns a failure response', function () {
    file_put_contents($this->logPath, "{\"type\":\"http.request\",\"message\":\"failed batch\"}\n");

    Http::fake([
        'https://example.test/ingest/v1/logs' => Http::response('', 500),
    ]);

    $this->artisan('observability:agent --once')->assertSuccessful();

    Http::assertSentCount(1);
});

it('handles a missing log file gracefully', function () {
    Http::fake();

    $this->artisan('observability:agent --once')->assertSuccessful();

    Http::assertNothingSent();
});
