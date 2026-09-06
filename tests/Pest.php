<?php

declare(strict_types=1);

use LinuusObservability\LinuUsObservability\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function observabilityTestLogPath(string $fileName): string
{
    return storage_path('framework/testing/'.$fileName);
}

function observabilityAgentStatePath(): string
{
    return storage_path('framework/cache/observability-agent.json');
}

function observabilityAgentRestartPath(): string
{
    return storage_path('framework/cache/observability-agent.restart');
}

/**
 * @param  array<int, string>  $paths
 */
function removeFiles(array $paths): void
{
    foreach ($paths as $path) {
        if (is_file($path)) {
            unlink($path);
        }
    }
}

/**
 * @return array<int, array<string, mixed>>
 *
 * @throws JsonException
 */
function readNdjson(string $path): array
{
    if (! is_file($path)) {
        return [];
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (! is_array($lines)) {
        return [];
    }

    return array_map(
        static fn (string $line): array => json_decode($line, true, 512, JSON_THROW_ON_ERROR),
        $lines,
    );
}
