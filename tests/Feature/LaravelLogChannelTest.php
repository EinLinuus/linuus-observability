<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;

beforeEach(function (): void {
    $this->logPath = observabilityTestLogPath('laravel-log-channel-events.jsonl');

    removeFiles([$this->logPath]);

    config()->set('observability.enabled', true);
    config()->set('observability.log_path', $this->logPath);
    config()->set('observability.service_name', 'demo-service');
    config()->set('observability.environment', 'testing');
    config()->set('observability.service_version', '1.0.0');
});

afterEach(function (): void {
    removeFiles([$this->logPath]);
});

it('captures regular laravel logs when linuus-observability is in the stack', function () {
    config()->set('logging.channels.linuus-observability', [
        'driver' => 'linuus-observability',
        'level' => 'debug',
    ]);
    config()->set('logging.channels.stack', [
        'driver' => 'stack',
        'channels' => ['linuus-observability'],
        'ignore_exceptions' => false,
    ]);
    config()->set('logging.default', 'stack');

    app('log')->forgetChannel();

    Log::info('regular laravel log captured', [
        'note_id' => 123,
    ]);

    $events = readNdjson($this->logPath);
    $event = collect($events)->firstWhere('message', 'regular laravel log captured');

    expect($event)->toBeArray();
    expect($event)->toMatchArray([
        'type' => 'log.message',
        'message' => 'regular laravel log captured',
        'level' => 'info',
        'service.name' => 'demo-service',
        'deployment.environment' => 'testing',
        'service.version' => '1.0.0',
    ]);
    expect($event)->toHaveKey('log.context');
    expect($event['log.context'])->toMatchArray([
        'note_id' => 123,
    ]);
});
