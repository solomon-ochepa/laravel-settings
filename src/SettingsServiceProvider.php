<?php

namespace SolomonOchepa\Settings;

use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        // Load & Publish config
        $configPath = __DIR__.'/../config/settings.php';
        $this->publishes([
            $configPath => config_path('settings.php'),
        ], 'config');

        // Load & Publish migration
        $this->loadMigrationsFrom(__DIR__.'/migrations');
        $this->publishes([__DIR__.'/migrations/' => database_path('/migrations/'),
        ], 'migrations');
    }

    /**
     * Register bindings in the container.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/settings.php', 'settings');

        // bind Settings repository
        $this->app->bind(
            'SolomonOchepa\Settings\Interfaces\SettingsInterface',
            'SolomonOchepa\Settings\Repositories\SettingsRepository'
        );
    }
}
