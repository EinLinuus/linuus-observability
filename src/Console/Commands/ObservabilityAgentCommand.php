<?php

declare(strict_types=1);

namespace LinuusObservability\LinuUsObservability\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ObservabilityAgentCommand extends Command
{
    protected $signature = 'observability:agent {--once : Process a single flush cycle and exit}';

    protected $description = 'Send observability JSONL events to the configured endpoint.';

    public function handle(): int
    {
        $flushIntervalSeconds = max(0, (int) config('observability.agent.flush_interval_seconds', 5));
        $retrySleepSeconds = max(0, (int) config('observability.agent.retry_sleep_seconds', 10));

        do {
            $batch = $this->readBatch();

            if ($batch['lines'] !== []) {
                $wasFlushed = $this->flushBatch($batch['lines']);

                if ($wasFlushed) {
                    $this->storeOffset($batch['next_offset']);
                } elseif (! $this->option('once')) {
                    sleep($retrySleepSeconds);
                }
            }

            if ($this->option('once')) {
                break;
            }

            sleep($flushIntervalSeconds);
        } while (true);

        return self::SUCCESS;
    }

    /**
     * @return array{lines: array<int, string>, next_offset: int}
     */
    private function readBatch(): array
    {
        $logPath = (string) config('observability.log_path');
        $offset = $this->loadOffset();

        if ($logPath === '' || ! is_file($logPath)) {
            return ['lines' => [], 'next_offset' => $offset];
        }

        $filesize = filesize($logPath);

        if ($filesize !== false && $offset > $filesize) {
            $offset = 0;
            $this->storeOffset($offset);
        }

        $handle = fopen($logPath, 'rb');

        if ($handle === false) {
            $this->warn(sprintf('Unable to open observability log file: %s', $logPath));

            return ['lines' => [], 'next_offset' => $offset];
        }

        fseek($handle, $offset);

        $batchSize = max(1, (int) config('observability.agent.batch_size', 100));
        $maxLineBytes = max(1, (int) config('observability.agent.max_line_bytes', 262144));

        $lines = [];
        $nextOffset = $offset;

        while (count($lines) < $batchSize) {
            $line = fgets($handle);

            if ($line === false) {
                break;
            }

            $nextOffset = (int) ftell($handle);

            if (strlen($line) > $maxLineBytes) {
                continue;
            }

            if (! str_ends_with($line, PHP_EOL)) {
                $line .= PHP_EOL;
            }

            $lines[] = $line;
        }

        fclose($handle);

        return ['lines' => $lines, 'next_offset' => $nextOffset];
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function flushBatch(array $lines): bool
    {
        $endpoint = trim((string) config('observability.endpoint'));
        $token = trim((string) config('observability.token'));

        if ($endpoint === '' || $token === '') {
            $this->warn('Set OBSERVABILITY_ENDPOINT and OBSERVABILITY_TOKEN before running the agent.');

            return false;
        }

        $body = implode('', $lines);

        try {
            $response = Http::withHeaders([
                'Authorization' => sprintf('Bearer %s', $token),
                'Content-Type' => 'application/x-ndjson',
            ])->withBody($body, 'application/x-ndjson')
                ->post($endpoint);
        } catch (ConnectionException $exception) {
            report($exception);

            return false;
        }

        if ($response->successful()) {
            return true;
        }

        $this->warn(sprintf('Agent flush failed with HTTP %d.', $response->status()));

        return false;
    }

    private function loadOffset(): int
    {
        $statePath = $this->statePath();

        if (! is_file($statePath)) {
            return 0;
        }

        $contents = file_get_contents($statePath);

        if (! is_string($contents) || $contents === '') {
            return 0;
        }

        $state = json_decode($contents, true);

        if (! is_array($state) || ! isset($state['offset'])) {
            return 0;
        }

        return max(0, (int) $state['offset']);
    }

    private function storeOffset(int $offset): void
    {
        $statePath = $this->statePath();
        $directory = dirname($statePath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents(
            $statePath,
            json_encode(['offset' => max(0, $offset)], JSON_THROW_ON_ERROR),
            LOCK_EX,
        );
    }

    private function statePath(): string
    {
        return storage_path('framework/cache/observability-agent.json');
    }
}
