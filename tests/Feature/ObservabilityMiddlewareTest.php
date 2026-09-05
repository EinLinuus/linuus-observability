<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    $this->logPath = observabilityTestLogPath('middleware-events.jsonl');

    removeFiles([$this->logPath]);

    config()->set('observability.enabled', true);
    config()->set('observability.log_path', $this->logPath);
    config()->set('observability.service_name', 'demo-service');
    config()->set('observability.environment', 'testing');
    config()->set('observability.service_version', '1.0.0');
});

afterEach(function (): void {
    removeFiles([$this->logPath]);
});

it('writes a valid http request event and returns a generated request id', function () {
    Route::middleware('observability.request')
        ->get('/posts/{post}', static fn () => response()->json(['ok' => true]));

    $response = $this->get('/posts/123');

    $response->assertOk()->assertHeader('X-Request-Id');

    $requestId = $response->headers->get('X-Request-Id');
    $events = readNdjson($this->logPath);

    expect($requestId)->not->toBeEmpty();
    expect($events)->toHaveCount(1);
    expect($events[0])->toMatchArray([
        'type' => 'http.request',
        'message' => 'http request completed',
        'level' => 'info',
        'service.name' => 'demo-service',
        'deployment.environment' => 'testing',
        'service.version' => '1.0.0',
        'request_id' => $requestId,
        'http.request.method' => 'GET',
        'url.path' => '/posts/123',
        'http.route' => '/posts/{post}',
        'http.response.status_code' => 200,
        'client.address' => '127.0.0.1',
        'user.id' => null,
    ]);
    expect($events[0]['timestamp'])->toBeString();
    expect($events[0]['duration_ms'])->toBeFloat();
});

it('preserves an incoming x-request-id header', function () {
    Route::middleware('observability.request')
        ->get('/request-id-preserved', static fn () => response('ok'));

    $response = $this->withHeader('X-Request-Id', 'request-abc-123')
        ->get('/request-id-preserved');

    $response->assertOk()->assertHeader('X-Request-Id', 'request-abc-123');

    $events = readNdjson($this->logPath);

    expect($events)->toHaveCount(1);
    expect($events[0]['request_id'])->toBe('request-abc-123');
});

it('normalizes url paths and includes route uri when available', function () {
    Route::middleware('observability.request')
        ->get('normalized-path/{id}', static fn () => response('ok'));

    $this->get('/normalized-path/1')->assertOk();

    $events = readNdjson($this->logPath);

    expect($events)->toHaveCount(1);
    expect($events[0]['url.path'])->toStartWith('/');
    expect($events[0]['http.route'])->toBe('/normalized-path/{id}');
});
