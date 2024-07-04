<?php

use Illuminate\Support\Arr;

if (! function_exists('settings')) {
    /**
     * Get setting(s) from the database or add a new one if an array is passed.
     */
    function settings(null|string|array $key = null, $default = null): mixed
    {
        $setting = app()->make('Oki\Settings\Interfaces\SettingInterface');

        if (is_null($key)) {
            return $setting;
        }

        if (is_array($key)) {
            return $setting->set($key);
        } else {
            return $setting->get($key, value($default));
        }
    }
}
