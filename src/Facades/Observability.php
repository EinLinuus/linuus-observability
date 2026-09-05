<?php

declare(strict_types=1);

namespace LinuusObservability\LinuUsObservability\Facades;

use Illuminate\Support\Facades\Facade;
use LinuusObservability\LinuUsObservability\LinuUsObservability;

/**
 * @method static void info(string $message, array<string, mixed> $attributes = [])
 * @method static void warning(string $message, array<string, mixed> $attributes = [])
 * @method static void audit(string $message, array<string, mixed> $attributes = [])
 * @method static void record(array<string, mixed> $event)
 *
 * @see LinuUsObservability
 */
class Observability extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LinuUsObservability::class;
    }
}
