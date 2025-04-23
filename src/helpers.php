<?php

use SolomonOchepa\Settings\Interfaces\SettingsInterface;

if (! function_exists('settings')) {
    /**
     * Get setting(s) from the database or add a new one if an array is passed.
     */
    function settings(null|string|array $key = null, $default = null): mixed
    {
        try {
            $settings = app(SettingsInterface::class);

            if (is_null($key)) {
                return $settings;
            }

            if (is_array($key)) {
                return $settings->add($key);
            } else {
                return $settings->get($key, value($default));
            }
        } catch (\Throwable $e) {
            return $default;
        }
    }
}
