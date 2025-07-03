<?php

namespace SolomonOchepa\Settings\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use SolomonOchepa\Settings\Tests\App\Models\User;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Load the migrations manually for testing
        $this->loadMigrationsFrom(__DIR__.'/App/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../src/database/migrations');
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set up auth
        $app['config']->set('auth.defaults.guard', 'web');
        $app['config']->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => User::class,
        ]);
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getPackageProviders($app): array
    {
        return ['SolomonOchepa\Settings\SettingsServiceProvider'];
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'Settings' => 'SolomonOchepa\Settings\Facades\Settings',
        ];
    }

    /**
     * Set inputs on settings ui
     */
    protected function configureInputs($inputs): void
    {
        config([
            'app_settings.sections' => [
                'app' => [
                    'title' => 'General Settings',
                    'descriptions' => 'Application general settings.',
                    'icon' => 'fa fa-cog',

                    'inputs' => $inputs,
                ],
            ],
        ]);
    }
}
