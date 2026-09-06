<?php

declare(strict_types=1);

namespace LinuusObservability\LinuUsObservability\Support;

use RuntimeException;

final class AgentRestartSignal
{
    public function current(): string
    {
        $path = $this->path();

        if (! is_file($path)) {
            return '';
        }

        $marker = file_get_contents($path);

        if ($marker === false) {
            throw new RuntimeException(sprintf('Unable to read agent restart marker: %s', $path));
        }

        return $marker;
    }

    public function restart(): void
    {
        $path = $this->path();
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create agent restart marker directory: %s', $directory));
        }

        if (file_put_contents($path, bin2hex(random_bytes(16)), LOCK_EX) === false) {
            throw new RuntimeException(sprintf('Unable to write agent restart marker: %s', $path));
        }
    }

    public function hasChanged(string $marker): bool
    {
        return $this->current() !== $marker;
    }

    private function path(): string
    {
        return storage_path('framework/cache/observability-agent.restart');
    }
}
