<?php

declare(strict_types=1);

namespace LinuusObservability\LinuUsObservability\Console\Commands;

use Illuminate\Console\Command;

class LinuUsObservabilityCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'linuus-observability:placeholder';

    /**
     * The command description.
     */
    protected $description = 'Placeholder Artisan command shipped by the package linuus-observability.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('LinuUsObservability placeholder command executed.');

        return self::SUCCESS;
    }
}
