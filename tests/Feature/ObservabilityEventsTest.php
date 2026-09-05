<?php

declare(strict_types=1);

use LinuusObservability\LinuUsObservability\Facades\Observability;
use LinuusObservability\LinuUsObservability\LinuUsObservability;

beforeEach(function (): void {
    $this->logPath = observabilityTestLogPath('custom-events.jsonl');

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

it('writes info and warning events to JSONL', function () {
    Observability::info('secure note created', [
        'note_id' => 42,
    ]);

    Observability::warning('ip geolocation failed', [
        'downstream.service' => 'ip-geolocation',
    ]);

    $events = readNdjson($this->logPath);

    expect($events)->toHaveCount(2);
    expect($events[0])->toMatchArray([
        'type' => 'app.event',
        'level' => 'info',
        'message' => 'secure note created',
        'note_id' => 42,
    ]);
    expect($events[1])->toMatchArray([
        'type' => 'app.event',
        'level' => 'warning',
        'message' => 'ip geolocation failed',
        'downstream.service' => 'ip-geolocation',
    ]);
});

it('writes audit events with the audit event type', function () {
    $observability = app(LinuUsObservability::class);

    $observability->audit('user.role_changed', [
        'actor.id' => 10,
        'target.user_id' => 20,
        'role' => 'admin',
    ]);

    $events = readNdjson($this->logPath);

    expect($events)->toHaveCount(1);
    expect($events[0])->toMatchArray([
        'type' => 'audit.event',
        'level' => 'info',
        'message' => 'user.role_changed',
        'actor.id' => 10,
        'target.user_id' => 20,
        'role' => 'admin',
    ]);
});
