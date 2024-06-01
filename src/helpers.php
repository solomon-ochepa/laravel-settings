<?php

if (! function_exists('settings')) {

    /**
     * Get app setting from database.
     */
    function settings(string|array|null $key = null, $default = null): mixed
    {
        $setting = app()->make('Oki\Settings\Interfaces\SettingInterface');

        if (is_null($key)) {
            // return $setting->all();
            return $setting;
        }

        if (is_array($key)) {
            return $setting->set($key);
        }

        return $setting->get($key, value($default));
    }
}
