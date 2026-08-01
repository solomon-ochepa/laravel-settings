<?php

declare(strict_types=1);

use SolomonOchepa\Settings\Models\Setting;

return [
    /*
     * Which Eloquent model should be used to retrieve your settings?
     * Typically, it is the 'Setting' model, but you can use whatever you prefer.
     *
     * Your custom model needs to implement the SolomonOchepa\Settings\Models\Setting class.
     */
    'model' => env('SETTINGS_MODEL', Setting::class),

    /*
     * Table name
     */
    'table' => env('SETTINGS_TABLE', 'settings'),

    /*
     * Table columns name
     */
    'columns' => [
        'name' => env('SETTINGS_COLUMNS_NAME', 'name'),
        'value' => env('SETTINGS_COLUMNS_VALUE', 'value'),
        'group' => env('SETTINGS_COLUMNS_GROUP', 'group'),
    ],

    'group' => [
        /*
         * The Settings default group(s)
         */
        'default' => env('SETTINGS_GROUP_DEFAULT', 'default'),
    ],

    'cache' => [
        /*
         * All settings are always cached to reduce SQL queries to zero.
         * The cache is flushed automatically whenever settings are written,
         * trashed, restored, or deleted.
         *
         * By default, settings are cached for 2 hours.
         */
        'ttl' => env('SETTINGS_CACHE_TTL', DateInterval::createFromDateString('2 hours')),

        /*
         * The cache key used to store all settings.
         */
        'key' => env('SETTINGS_CACHE_KEY', 'settings'),

        /*
         * You may optionally specify a particular cache driver for setting caching
         * by using any of the `store` drivers listed in the `cache.php` configuration file.
         *
         * Using 'default' here means the `default` driver set in `cache.php` will be used.
         */
        'store' => env('SETTINGS_CACHE_STORE', 'default'),
    ],

    'user' => [
        'model' => null, // App\Models\User::class
    ],
];
