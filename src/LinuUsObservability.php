<?php

declare(strict_types=1);

namespace LinuusObservability\LinuUsObservability;

use LinuusObservability\LinuUsObservability\Support\JsonlEventWriter;
use Throwable;

class LinuUsObservability
{
    private bool $reportingFailure = false;

    public function __construct(
        private readonly JsonlEventWriter $writer,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function info(string $message, array $attributes = []): void
    {
        $this->event('app.event', 'info', $message, $attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function warning(string $message, array $attributes = []): void
    {
        $this->event('app.event', 'warning', $message, $attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function audit(string $message, array $attributes = []): void
    {
        $this->event('audit.event', 'info', $message, $attributes);
    }

    /**
     * @param  array<string, mixed>  $event
     */
    public function record(array $event): void
    {
        if (! (bool) config('observability.enabled', true)) {
            return;
        }

        $payload = array_merge($this->serviceMetadata(), $event);
        $payload['timestamp'] ??= now()->toISOString();
        $payload['level'] ??= 'info';
        $payload['type'] ??= 'app.event';
        $payload['message'] ??= 'observability event';

        try {
            $this->writer->append($payload);
        } catch (Throwable $exception) {
            if ($this->reportingFailure) {
                return;
            }

            $this->reportingFailure = true;

            try {
                report($exception);
            } finally {
                $this->reportingFailure = false;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function event(string $type, string $level, string $message, array $attributes = []): void
    {
        $this->record(array_merge([
            'type' => $type,
            'timestamp' => now()->toISOString(),
            'message' => $message,
            'level' => $level,
        ], $attributes));
    }

    /**
     * @return array<string, mixed>
     */
    private function serviceMetadata(): array
    {
        return [
            'service.name' => config('observability.service_name'),
            'deployment.environment' => config('observability.environment'),
            'service.version' => config('observability.service_version'),
        ];
    }
}
