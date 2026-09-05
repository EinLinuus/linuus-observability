<?php

declare(strict_types=1);

namespace LinuusObservability\LinuUsObservability\Logging;

use LinuusObservability\LinuUsObservability\LinuUsObservability;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

class ObservabilityLogHandler extends AbstractProcessingHandler
{
    public function __construct(
        private readonly LinuUsObservability $observability,
        int|string|Level $level = 'debug',
        bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        $event = [
            'type' => 'log.message',
            'timestamp' => $record->datetime->format(DATE_ATOM),
            'message' => $record->message,
            'level' => strtolower($record->level->getName()),
            'log.channel' => $record->channel,
        ];

        if ($record->context !== []) {
            $event['log.context'] = $record->context;
        }

        if ($record->extra !== []) {
            $event['log.extra'] = $record->extra;
        }

        $this->observability->record($event);
    }
}
