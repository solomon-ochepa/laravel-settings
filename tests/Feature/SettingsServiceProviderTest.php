<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;
use SolomonOchepa\Settings\Facades\Settings;
use SolomonOchepa\Settings\Interfaces\SettingsInterface;
use SolomonOchepa\Settings\Models\Setting;
use SolomonOchepa\Settings\Repositories\SettingsRepository;
use SolomonOchepa\Settings\SettingsServiceProvider;

it('binds a new SettingsRepository instance on every resolution', function () {
    // bind(), not singleton(), so state set on one resolution (group, for,
    // flush, ...) must never leak into another.
    $first = app(SettingsInterface::class);
    $second = app(SettingsInterface::class);

    expect($first)->not->toBe($second);
    expect($first)->toBeInstanceOf(SettingsRepository::class);
});

it('registers the "settings" container alias for the same contract', function () {
    expect(app('settings'))->toBeInstanceOf(SettingsInterface::class);
});

it('uses a custom model configured via settings.model for storage', function () {
    config(['settings.model' => Setting::class]);

    Settings::set('via_custom_model', 'value');

    expect(Settings::get('via_custom_model'))->toEqual('value');
});

it('publishes the config and migration when running in console', function () {
    (new SettingsServiceProvider($this->app))->boot();

    expect(ServiceProvider::pathsToPublish(SettingsServiceProvider::class, 'settings:config'))->not->toBeEmpty();
    expect(ServiceProvider::pathsToPublish(SettingsServiceProvider::class, 'settings:migrations'))->not->toBeEmpty();
});

it('does not attempt to publish anything when not running in console', function () {
    $app = Mockery::mock();
    $app->shouldReceive('runningInConsole')->once()->andReturn(false);

    // If publish() proceeded past this guard, it would call other,
    // undefined methods on this bare double and throw.
    expect(fn () => (new SettingsServiceProvider($app))->boot())->not->toThrow(Throwable::class);
});
