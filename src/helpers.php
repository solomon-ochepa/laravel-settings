<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use SolomonOchepa\Settings\Interfaces\SettingsInterface;

if (! function_exists('settings')) { // @codeCoverageIgnore
    // This guard cannot be exercised by tests: helpers.php loads once via
    // Composer's "files" autoloader before any test runs, so the false
    // branch (another package already defining settings()) can never be
    // triggered from within this suite. It stays to prevent a real
    // "Cannot redeclare function" fatal error if that ever happens.
    /**
     * Get setting(s) from the database or add a new one if an array is passed.
     *
     * Usage:
     * - settings() => get the SettingsInterface instance
     * - settings('name') => get a specific setting value
     * - settings(['name' => 'value']) => add new setting(s)
     */
    function settings(null|string|array $key = null, mixed $default = null): mixed
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
        } catch (Throwable $e) {
            Log::error($e->getMessage());

            return value($default);
        }
    }
}
