<?php

return [
    /*
     * Which Eloquent model should be used to retrieve your settings?
     * Typically, it is the 'Setting' model, but you can use whatever you prefer.
     *
     * Your custom model needs to implement the SolomonOchepa\Settings\Models\Setting class.
     */
    'model' => env('SETTINGS_MODEL', SolomonOchepa\Settings\Models\Setting::class),

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
    ],

    'cache' => [
        /*
         * By default, all settings are cached for 24 hours to enhance performance.
         *
         * When settings are updated, the cache is automatically flushed.
         */
        'timeout' => env('SETTINGS_CACHE_TIMEOUT', \DateInterval::createFromDateString('24 hours')),

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

    'group' => [
        /*
         * ...
         */
        'default' => env('SETTINGS_GROUP_DEFAULT', 'default'),
    ],
];
