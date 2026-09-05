<?php

declare(strict_types=1);

namespace LinuusObservability\LinuUsObservability\Support;

use Illuminate\Filesystem\Filesystem;
use JsonException;
use RuntimeException;

class JsonlEventWriter
{
    public function __construct(
        private readonly Filesystem $filesystem,
    ) {}

    /**
     * @param  array<string, mixed>  $event
     *
     * @throws JsonException
     * @throws RuntimeException
     */
    public function append(array $event): void
    {
        $path = (string) config('observability.log_path');

        if ($path === '') {
            throw new RuntimeException('The observability.log_path config value is empty.');
        }

        $this->filesystem->ensureDirectoryExists(dirname($path));

        $line = json_encode($event, JSON_THROW_ON_ERROR).PHP_EOL;
        $written = file_put_contents($path, $line, FILE_APPEND | LOCK_EX);

        if ($written === false) {
            throw new RuntimeException(sprintf('Unable to write observability event to "%s".', $path));
        }
    }
}
