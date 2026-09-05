<?php

declare(strict_types=1);

namespace LinuusObservability\LinuUsObservability\Console\Commands;

use Illuminate\Console\Command;

class ObservabilityInstallCommand extends Command
{
    protected $signature = 'observability:install';

    protected $description = 'Install linu.us observability package resources.';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'linuus-observability-config',
        ]);

        $this->newLine();
        $this->components->info('Add these environment variables to your .env file:');
        $this->line('OBSERVABILITY_ENABLED=true');
        $this->line('OBSERVABILITY_SERVICE_NAME="${APP_NAME}"');
        $this->line('OBSERVABILITY_ENVIRONMENT="${APP_ENV}"');
        $this->line('OBSERVABILITY_SERVICE_VERSION="${APP_VERSION}"');
        $this->line('OBSERVABILITY_LOG_PATH="storage/logs/observability.jsonl"');
        $this->line('OBSERVABILITY_ENDPOINT="https://your-endpoint.test/ingest/v1/logs"');
        $this->line('OBSERVABILITY_TOKEN="replace-me"');
        $this->line('OBSERVABILITY_AGENT_BATCH_SIZE=100');
        $this->line('OBSERVABILITY_AGENT_FLUSH_INTERVAL_SECONDS=5');
        $this->line('OBSERVABILITY_AGENT_RETRY_SLEEP_SECONDS=10');
        $this->line('OBSERVABILITY_AGENT_MAX_LINE_BYTES=262144');

        $this->newLine();
        $this->components->info('Register middleware where you need request logging:');
        $this->line("Route::middleware('observability.request')->group(fn () => ...);");
        $this->line('The package does not register this middleware globally by default.');

        return self::SUCCESS;
    }
}
