<?php

declare(strict_types=1);

namespace LinuusObservability\LinuUsObservability;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Log\LogManager;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use LinuusObservability\LinuUsObservability\Console\Commands\ObservabilityAgentCommand;
use LinuusObservability\LinuUsObservability\Console\Commands\ObservabilityInstallCommand;
use LinuusObservability\LinuUsObservability\Console\Commands\ObservabilityRestartAgentCommand;
use LinuusObservability\LinuUsObservability\Http\Middleware\RecordHttpRequest;
use LinuusObservability\LinuUsObservability\Logging\CreateObservabilityLogger;
use LinuusObservability\LinuUsObservability\Support\AgentRestartSignal;
use LinuusObservability\LinuUsObservability\Support\JsonlEventWriter;

class LinuUsObservabilityServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/observability.php', 'observability');

        $this->app->singleton(AgentRestartSignal::class);
        $this->app->singleton(JsonlEventWriter::class);
        $this->app->singleton(LinuUsObservability::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->afterResolving(LogManager::class, function (LogManager $logManager): void {
            $logManager->extend('linuus-observability', function (Application $app, array $config) {
                return $app->make(CreateObservabilityLogger::class)($config);
            });
        });

        $this->callAfterResolving(Router::class, function (Router $router): void {
            $router->aliasMiddleware('observability.request', RecordHttpRequest::class);
        });

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/observability.php' => config_path('observability.php'),
            ], ['linuus-observability', 'linuus-observability-config']);

            $this->commands([
                ObservabilityInstallCommand::class,
                ObservabilityAgentCommand::class,
                ObservabilityRestartAgentCommand::class,
            ]);
        }
    }
}
