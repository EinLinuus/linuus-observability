<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use LinuusObservability\LinuUsObservability\LinuUsObservability;

it('resolves the singleton and merges config', function () {
    expect(app(LinuUsObservability::class))->toBeInstanceOf(LinuUsObservability::class);
    expect(config('observability.enabled'))->toBeTrue();
});

it('registers package commands', function () {
    $commands = array_keys(app(Kernel::class)->all());

    expect($commands)->toContain('observability:install', 'observability:agent');
});
