<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use SolomonOchepa\Settings\Interfaces\SettingsInterface;

it('returns the SettingsInterface instance when called with no arguments', function () {
    expect(settings())->toBeInstanceOf(SettingsInterface::class);
});

it('evaluates a closure default on the happy path', function () {
    $result = settings('missing_key', fn () => 'lazily_computed');

    expect($result)->toEqual('lazily_computed');
});

it('logs and gracefully falls back to a resolved default on internal failure', function () {
    config(['settings.model' => 'NonExistentModelClass']);

    Log::shouldReceive('error')->once();

    $result = settings('any_key', fn () => 'fallback_value');

    expect($result)->toEqual('fallback_value');
});

it('recovers gracefully for the array (set) shorthand on internal failure', function () {
    config(['settings.model' => 'NonExistentModelClass']);

    Log::shouldReceive('error')->once();

    $result = settings(['key' => 'value']);

    expect($result)->toBeNull();
});
