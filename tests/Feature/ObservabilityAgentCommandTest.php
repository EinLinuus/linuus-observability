<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use LinuusObservability\LinuUsObservability\Support\AgentRestartSignal;

beforeEach(function (): void {
    $this->logPath = observabilityTestLogPath('agent-events.jsonl');
    $this->statePath = observabilityAgentStatePath();
    $this->restartPath = observabilityAgentRestartPath();

    removeFiles([$this->logPath, $this->statePath, $this->restartPath]);

    config()->set('observability.log_path', $this->logPath);
    config()->set('observability.endpoint', 'https://example.test/ingest/v1/logs');
    config()->set('observability.token', 'secret-token');
    config()->set('observability.agent.batch_size', 100);
    config()->set('observability.agent.flush_interval_seconds', 0);
    config()->set('observability.agent.retry_sleep_seconds', 0);
    config()->set('observability.agent.max_line_bytes', 262144);
});

afterEach(function (): void {
    removeFiles([$this->logPath, $this->statePath, $this->restartPath]);
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

it('requests a graceful agent restart', function () {
    $this->artisan('observability:restart-agent')
        ->expectsOutputToContain('restart requested')
        ->assertSuccessful();

    expect(file_get_contents($this->restartPath))->toBeString()->not->toBe('');
});

it('exits after the current flush when a restart is requested', function () {
    file_put_contents($this->logPath, "{\"type\":\"http.request\",\"message\":\"restart batch\"}\n");
    app(AgentRestartSignal::class)->restart();

    Http::fake(function () {
        app(AgentRestartSignal::class)->restart();

        return Http::response('', 202);
    });

    $this->artisan('observability:agent')->assertSuccessful();

    Http::assertSentCount(1);

    $state = json_decode((string) file_get_contents($this->statePath), true, 512, JSON_THROW_ON_ERROR);

    expect($state['offset'])->toBe(filesize($this->logPath));
});
