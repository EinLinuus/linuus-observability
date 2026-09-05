<?php

declare(strict_types=1);

use LinuusObservability\LinuUsObservability\LinuUsObservability;

it('resolves the singleton', function () {
    expect(app(LinuUsObservability::class))->toBeInstanceOf(LinuUsObservability::class);
});

it('returns the same instance from the container', function () {
    expect(app(LinuUsObservability::class))->toBe(app(LinuUsObservability::class));
});

it('merges the package config', function () {
    expect(config('linuus-observability.placeholder'))->toBe('default');
});

it('registers the artisan command', function () {
    $this->artisan('linuus-observability:placeholder')
        ->expectsOutputToContain('LinuUsObservability placeholder command executed.')
        ->assertSuccessful();
});
