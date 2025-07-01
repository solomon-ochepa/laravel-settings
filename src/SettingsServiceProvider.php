<?php

namespace SolomonOchepa\Settings;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use SolomonOchepa\Settings\Interfaces\SettingsInterface;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        $this->publish();
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

    protected function publish(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        if (! function_exists('config_path')) {
            // function not available and 'publish' not relevant in Lumen
            return;
        }

        // Load & Publish config
        $this->publishes([
            __DIR__.'/../config/settings.php' => config_path('settings.php'),
        ], 'settings-config');

        // Load & Publish migration
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->publishes([
            __DIR__.'/database/migrations/create_settings_table.php' => $this->getMigrationFileName('create_settings_table.php'),
        ], 'settings-migrations');
    }

    /**
     * Returns existing migration file if found, else uses the current timestamp.
     */
    protected function getMigrationFileName(string $migrationFileName): string
    {
        $timestamp = date('Y_m_d_His');

        $filesystem = $this->app->make(Filesystem::class);

        return Collection::make([$this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR])
            ->flatMap(fn ($path) => $filesystem->glob($path.'*_'.$migrationFileName))
            ->push($this->app->databasePath()."/migrations/{$timestamp}_{$migrationFileName}")
            ->first();
    }
}
