<?php

declare(strict_types=1);

use Illuminate\Support\Env;

return [
    'enabled' => Env::get('OBSERVABILITY_ENABLED', true),

    'service_name' => Env::get('OBSERVABILITY_SERVICE_NAME', Env::get('APP_NAME', 'laravel-app')),
    'environment' => Env::get('OBSERVABILITY_ENVIRONMENT', Env::get('APP_ENV', 'production')),
    'service_version' => Env::get('OBSERVABILITY_SERVICE_VERSION', Env::get('APP_VERSION', 'unknown')),

    'log_path' => Env::get('OBSERVABILITY_LOG_PATH', storage_path('logs/observability.jsonl')),

    'endpoint' => Env::get('OBSERVABILITY_ENDPOINT'),
    'token' => Env::get('OBSERVABILITY_TOKEN'),

    'agent' => [
        'batch_size' => Env::get('OBSERVABILITY_AGENT_BATCH_SIZE', 100),
        'flush_interval_seconds' => Env::get('OBSERVABILITY_AGENT_FLUSH_INTERVAL_SECONDS', 5),
        'retry_sleep_seconds' => Env::get('OBSERVABILITY_AGENT_RETRY_SLEEP_SECONDS', 10),
        'max_line_bytes' => Env::get('OBSERVABILITY_AGENT_MAX_LINE_BYTES', 262144),
    ],
];
