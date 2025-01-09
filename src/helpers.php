<?php

if (! function_exists('settings')) {
    /**
     * Get setting(s) from the database or add a new one if an array is passed.
     */
    function settings(null|string|array $key = null, $default = null): mixed
    {
        $settings = app()->make('SolomonOchepa\Settings\Interfaces\SettingsInterface');

        if (is_null($key)) {
            return $settings;
        }

        if (is_array($key)) {
            return $settings->add($key);
        } else {
            return $settings->get($key, value($default));
        }
    }
}
