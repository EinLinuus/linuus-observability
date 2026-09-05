<?php

declare(strict_types=1);

namespace LinuusObservability\LinuUsObservability\Logging;

use LinuusObservability\LinuUsObservability\LinuUsObservability;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;

class CreateObservabilityLogger
{
    public function __construct(
        private readonly LinuUsObservability $observability,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public function __invoke(array $config): Logger
    {
        return new Logger(
            name: (string) ($config['name'] ?? 'linuus-observability'),
            handlers: [
                new ObservabilityLogHandler(
                    observability: $this->observability,
                    level: $config['level'] ?? 'debug',
                    bubble: (bool) ($config['bubble'] ?? true),
                ),
            ],
            processors: [
                new PsrLogMessageProcessor,
            ],
        );
    }
}
