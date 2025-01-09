<?php

namespace SolomonOchepa\Settings\Interfaces;

use Illuminate\Support\Collection;

interface SettingsInterface
{
    /**
     * Get all settings from storage as key value pair.
     */
    public function all(bool $cached = true): Collection;

    /**
     * Get settings for the auth() user.
     */
    public function my(string $key, mixed $default = null): mixed;

    /**
     * Get a setting from storage by key.
     */
    public function get(string $key, mixed $default = null, bool $cached = true): mixed;

    /**
     * Save a setting in storage.
     */
    public function add(string $key, mixed $value = null): mixed;

    /**
     * Check if setting with key exists.
     */
    public function has(string $key): bool;

    /**
     * Trash a setting from storage.
     */
    public function trash(string $key): mixed;

    /**
     * Restore a setting from storage.
     */
    public function restore(string $key): mixed;

    /**
     * Permanently delete a setting from storage.
     */
    public function delete(string $key): mixed;

    /**
     * Flush setting cache.
     */
    public function flush(): bool;

    /**
     * Set the group name for settings.
     */
    public function group(string $name): self;

    /**
     * Bind settings to a specific entity.
     */
    public function for($settable_type, $settable_id = null): self;

    /**
     * Bind settings to the auth user.
     */
    public function user(): self;
}
