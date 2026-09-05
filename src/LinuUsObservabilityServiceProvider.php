<?php

declare(strict_types=1);

namespace LinuusObservability\LinuUsObservability;

use Illuminate\Support\ServiceProvider;
use LinuusObservability\LinuUsObservability\Console\Commands\LinuUsObservabilityCommand;

class LinuUsObservabilityServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/linuus-observability.php', 'linuus-observability');

        $this->app->singleton(LinuUsObservability::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/linuus-observability.php' => config_path('linuus-observability.php'),
        ], ['linuus-observability', 'linuus-observability-config']);

        $this->commands([
            LinuUsObservabilityCommand::class,
        ]);
    }
}
