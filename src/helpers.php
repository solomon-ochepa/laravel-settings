<?php

use Illuminate\Support\Facades\Log;
use SolomonOchepa\Settings\Interfaces\SettingsInterface;

if (! function_exists('settings')) {
    /**
     * Get setting(s) from the database or add a new one if an array is passed.
     *
     * Usage:
     * - settings('name') => get a specific setting value
     * - settings(['name' => 'value']) => add new setting(s)
     */
    function settings(null|string|array $key = null, $default = null): ?SettingsInterface
    {
        try {
            $settings = app(SettingsInterface::class);

            if (is_null($key)) {
                return $settings;
            }

            if (is_array($key)) {
                return $settings->set($key);
            }

            return $settings->get($key, value($default));
        } catch (\Throwable $e) {
            Log::error($e->getMessage());

            return $default;
        }
    }
}
