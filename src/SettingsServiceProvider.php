<?php

namespace SolomonOchepa\Settings;

use Illuminate\Support\ServiceProvider;
use SolomonOchepa\Settings\Interfaces\SettingsInterface;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        // Load & Publish config
        $this->publishes([
            __DIR__.'/../config/settings.php' => config_path('settings.php'),
        ], 'config');

        // Load & Publish migration
        $this->loadMigrationsFrom(__DIR__.'/migrations');
        $this->publishes([
            __DIR__.'/migrations/' => database_path('/migrations/'),
        ], 'migrations');
    }

    /**
     * Register bindings in the container.
     */
    public function register(): void
    {
        $this->app->alias(SettingsInterface::class, 'settings');

        $this->mergeConfigFrom(__DIR__.'/../config/settings.php', 'settings');

        // bind Settings repository
        $this->app->bind(
            'SolomonOchepa\Settings\Interfaces\SettingsInterface',
            'SolomonOchepa\Settings\Repositories\SettingsRepository'
        );
    }
}
