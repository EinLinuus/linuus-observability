<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware as InertiaMiddleware;

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

it('classifies Inertia validation redirects as unprocessable responses', function () {
    Route::middleware([
        StartSession::class,
        InertiaMiddleware::class,
        'observability.request',
    ])->post('/inertia-posts', static function (Request $request) {
        $request->validate([
            'title' => ['required'],
        ]);

        return response()->noContent();
    });

    $response = $this
        ->from('/inertia-posts/create')
        ->withHeader('X-Inertia', 'true')
        ->post('/inertia-posts');

    $response
        ->assertStatus(302)
        ->assertRedirect('/inertia-posts/create');

    $events = readNdjson($this->logPath);

    expect($events)->toHaveCount(1);
    expect($events[0])->toMatchArray([
        'http.response.status_code' => 422,
        'http.response.redirect_status_code' => 302,
    ]);
});

it('keeps successful Inertia redirects as redirects', function () {
    Route::middleware([
        StartSession::class,
        InertiaMiddleware::class,
        'observability.request',
    ])->post('/inertia-posts/success', static fn () => redirect('/inertia-posts'));

    $response = $this
        ->withHeader('X-Inertia', 'true')
        ->post('/inertia-posts/success');

    $response->assertRedirect('/inertia-posts');

    $events = readNdjson($this->logPath);

    expect($events)->toHaveCount(1);
    expect($events[0]['http.response.status_code'])->toBe(302);
    expect($events[0])->not->toHaveKey('http.response.redirect_status_code');
});

it('keeps non-Inertia validation redirects as redirects', function () {
    Route::middleware([
        StartSession::class,
        'observability.request',
    ])->post('/traditional-posts', static function (Request $request) {
        $request->validate([
            'title' => ['required'],
        ]);

        return response()->noContent();
    });

    $response = $this
        ->from('/traditional-posts/create')
        ->post('/traditional-posts');

    $response->assertRedirect('/traditional-posts/create');

    $events = readNdjson($this->logPath);

    expect($events)->toHaveCount(1);
    expect($events[0]['http.response.status_code'])->toBe(302);
    expect($events[0])->not->toHaveKey('http.response.redirect_status_code');
});

it('keeps server error status codes for Inertia requests', function () {
    Route::middleware([
        StartSession::class,
        InertiaMiddleware::class,
        'observability.request',
    ])->get('/inertia-error', static function (): never {
        throw new RuntimeException('Inertia server error');
    });

    $response = $this
        ->withHeader('X-Inertia', 'true')
        ->get('/inertia-error');

    $response->assertServerError();

    $events = readNdjson($this->logPath);

    expect($events)->toHaveCount(1);
    expect($events[0]['http.response.status_code'])->toBe(500);
    expect($events[0])->not->toHaveKey('http.response.redirect_status_code');
});
