<?php

declare(strict_types=1);

namespace LinuusObservability\LinuUsObservability\Console\Commands;

use Illuminate\Console\Command;
use LinuusObservability\LinuUsObservability\Support\AgentRestartSignal;

class ObservabilityRestartAgentCommand extends Command
{
    protected $signature = 'observability:restart-agent';

    protected $description = 'Request a graceful restart of the observability agent.';

    public function __construct(private readonly AgentRestartSignal $restartSignal)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->restartSignal->restart();

        $this->components->info('Observability agent restart requested.');

        return self::SUCCESS;
    }
}
