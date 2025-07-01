<?php

namespace SolomonOchepa\Settings\Interfaces;

use Illuminate\Support\Collection;

interface SettingsInterface
{
    /**
     * Set/Get the group name for settings.
     */
    public function group(string|array $name): self;

    /**
     * Bind settings to a specific entity.
     */
    public function for(string|object $settable): self;

    /**
     * Bind settings to the auth user.
     */
    public function user(): self;

    /**
     * Get all settings from storage as key value pair.
     */
    public function all(): Collection;

    /**
     * Get settings for the auth() user.
     */
    public function my(string $key, mixed $default = null): mixed;

    /**
     * Get a setting from storage by key.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Save a setting in storage and return the value.
     */
    public function set(string|array $key, mixed $value = null): mixed;

    /**
     * Check if a setting exists.
     */
    public function has(string $key): bool;

    /**
     * Check if a setting is missing.
     */
    public function missing(string $key): bool;

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
}
