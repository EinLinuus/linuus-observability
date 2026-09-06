<div align="center">
    <h1>linu.us Observability</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/einlinuus/linuus-observability"><img src="https://img.shields.io/packagist/v/einlinuus/linuus-observability.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/einlinuus/linuus-observability"><img src="https://img.shields.io/packagist/php-v/einlinuus/linuus-observability.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/einlinuus/linuus-observability"><img src="https://badge.laravel.cloud/badge/einlinuus/linuus-observability?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/einlinuus/linuus-observability/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/einlinuus/linuus-observability/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/einlinuus/linuus-observability"><img src="https://img.shields.io/packagist/dt/einlinuus/linuus-observability.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Observability agent for the linu.us observability system

## Installation

You can install the package via Composer:

```bash
composer require einlinuus/linuus-observability
```

Install the package config and setup guidance:

```bash
php artisan observability:install
```

Or publish only the config file manually:

```bash
php artisan vendor:publish --tag="linuus-observability-config"
```

## Configuration

The package publishes `config/observability.php`:

```php
return [
    'enabled' => env('OBSERVABILITY_ENABLED', true),

    'service_name' => env('OBSERVABILITY_SERVICE_NAME', env('APP_NAME', 'laravel-app')),
    'environment' => env('OBSERVABILITY_ENVIRONMENT', env('APP_ENV', 'production')),
    'service_version' => env('OBSERVABILITY_SERVICE_VERSION', env('APP_VERSION', 'unknown')),

    'log_path' => env('OBSERVABILITY_LOG_PATH', storage_path('logs/observability.jsonl')),

    'endpoint' => env('OBSERVABILITY_ENDPOINT'),
    'token' => env('OBSERVABILITY_TOKEN'),

    'agent' => [
        'batch_size' => env('OBSERVABILITY_AGENT_BATCH_SIZE', 100),
        'flush_interval_seconds' => env('OBSERVABILITY_AGENT_FLUSH_INTERVAL_SECONDS', 5),
        'retry_sleep_seconds' => env('OBSERVABILITY_AGENT_RETRY_SLEEP_SECONDS', 10),
        'max_line_bytes' => env('OBSERVABILITY_AGENT_MAX_LINE_BYTES', 262144),
    ],
];
```

## Middleware registration

The package makes middleware available as `observability.request`, but does not register it globally.

Apply it where you want request-completed events:

```php
use Illuminate\Support\Facades\Route;

Route::middleware('observability.request')->group(function (): void {
    Route::get('/posts/{post}', ...);
});
```

### Inertia validation responses

When `inertiajs/inertia-laravel` is installed, the middleware automatically recognizes Inertia validation redirects from their newly flashed Laravel error bag. These events use `422` for `http.response.status_code` so validation failures are not classified as successful redirects, while `http.response.redirect_status_code` preserves the observed `302` or `303`.

Successful Inertia redirects, non-Inertia responses, and server errors keep their original status code. No Inertia configuration is required, and validation messages or submitted values are not recorded.

## Capture regular Laravel logs (`Log::info()`)

Add a `linuus-observability` channel in `config/logging.php`:

```php
'channels' => [
    // ...
    'linuus-observability' => [
        'driver' => 'linuus-observability',
        'level' => env('LOG_LEVEL', 'debug'),
    ],
],
```

Then include it in your stack channel:

```php
'stack' => [
    'driver' => 'stack',
    'channels' => ['single', 'linuus-observability'],
],
```

After that, normal Laravel log calls are captured to the same JSONL stream:

```php
Log::info('secure note created', ['note_id' => 42]);
```

## Custom and audit events

```php
use Observability;

Observability::info('secure note created', [
    'note_id' => $note->id,
]);

Observability::warning('ip geolocation failed', [
    'downstream.service' => 'ip-geolocation',
]);

Observability::audit('user.role_changed', [
    'actor.id' => $user->id,
    'target.user_id' => $target->id,
    'role' => 'admin',
]);
```

All events are written to local newline-delimited JSON at `observability.log_path`.

## Run the local agent

Start the shipping process:

```bash
php artisan observability:agent
```

The agent:

1. Reads `observability.log_path`.
2. Tracks byte offset in `storage/framework/cache/observability-agent.json`.
3. Sends batches to `OBSERVABILITY_ENDPOINT` with bearer auth.
4. Retries failures without deleting local app logs.

### Ingest payload format (stable for MVP)

The agent sends NDJSON over HTTP:

```http
POST /ingest/v1/logs
Authorization: Bearer <token>
Content-Type: application/x-ndjson
```

```json
{"type":"http.request",...}
{"type":"audit.event",...}
```

## Continuous process examples

Run `php artisan observability:agent` as a long-running process in your host environment:

- **Laravel Forge:** Daemon command `php artisan observability:agent`
- **Laravel Cloud:** Worker/continuous process command `php artisan observability:agent`

## Example log lines

```json
{"type":"http.request","timestamp":"2026-09-05T11:02:03.456Z","message":"http request completed","level":"info","service.name":"laravel-app","deployment.environment":"production","service.version":"1.0.0","request_id":"6f95e154-2a4f-4de9-9324-fa82440ce34f","http.request.method":"GET","url.path":"/posts/123","http.route":"/posts/{post}","http.response.status_code":200,"duration_ms":12.34,"client.address":"127.0.0.1","user.id":null}
{"type":"log.message","timestamp":"2026-09-05T11:02:03.999Z","message":"secure note created","level":"info","service.name":"laravel-app","deployment.environment":"production","service.version":"1.0.0","log.channel":"linuus-observability","log.context":{"note_id":42}}
{"type":"audit.event","timestamp":"2026-09-05T11:02:04.100Z","message":"user.role_changed","level":"info","service.name":"laravel-app","deployment.environment":"production","service.version":"1.0.0","actor.id":1,"target.user_id":55,"role":"admin"}
```

## Current scope

- Laravel-side structured event producer (JSONL)
- Request middleware (`observability.request`)
- Optional Laravel log channel driver (`linuus-observability`) for regular `Log::*` capture
- Custom info/warning event API
- Explicit audit event API
- Local shipping agent command

## Non-goals (MVP)

- Grafana/Loki setup
- OpenTelemetry traces
- Metrics collection
- WordPress support
- Custom central ingest service implementation
- Direct Loki writes from Laravel
- Synchronous network logging from request code

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to linu.us Observability! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [EinLinuus](https://github.com/einlinuus)
- [All Contributors](../../contributors)

## License

linu.us Observability is open-sourced software licensed under the [MIT license](LICENSE.md).
