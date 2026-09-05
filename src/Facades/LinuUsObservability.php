<?php

declare(strict_types=1);

namespace LinuusObservability\LinuUsObservability\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \LinuusObservability\LinuUsObservability\LinuUsObservability
 */
class LinuUsObservability extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \LinuusObservability\LinuUsObservability\LinuUsObservability::class;
    }
}
