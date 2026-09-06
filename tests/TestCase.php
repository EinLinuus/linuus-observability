<?php

declare(strict_types=1);

namespace LinuusObservability\LinuUsObservability\Tests;

use Inertia\ServiceProvider as InertiaServiceProvider;
use LinuusObservability\LinuUsObservability\LinuUsObservabilityServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LinuUsObservabilityServiceProvider::class,
            InertiaServiceProvider::class,
        ];
    }
}
