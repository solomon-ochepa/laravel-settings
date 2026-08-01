<?php

declare(strict_types=1);

namespace SolomonOchepa\Settings\Interfaces;

use Illuminate\Support\Collection;

interface SettingsInterface
{
    /**
     * Scope settings to one or more groups.
     *
     * Passing multiple groups makes `all()` return a collection keyed by
     * group name instead of a flat key/value collection.
     */
    public function group(string|array $name): self;

    /**
     * Scope settings to a specific entity, e.g. an Eloquent model instance.
     *
     * Passing a falsy value (null, empty string, etc.) clears the scope so
     * subsequent calls read/write global, unscoped settings.
     */
    public function for(null|string|object $settable = null): self;

    /**
     * Scope settings to the currently authenticated user.
     *
     * Falls back to the global, unscoped settings when there is no
     * authenticated user (e.g. a guest request).
     */
    public function user(): self;

    /**
     * Get all settings in the current group/settable scope as a key/value
     * collection, served from cache when available.
     */
    public function all(): Collection;

    /**
     * Get a setting scoped to the currently authenticated user.
     */
    public function my(string $key, mixed $default = null): mixed;

    /**
     * Get a setting from storage by key, or set and return $default if the
     * key does not already exist. An existing falsy value (null, false, 0,
     * '') is treated as present and is returned as-is, not overwritten.
     */
    public function remember(string $key, mixed $default): mixed;

    /**
     * Get a setting from storage by key.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Save a setting in storage and return the value.
     */
    public function set(string|array $key, mixed $value = null): mixed;

    /**
     * Alias for set().
     */
    public function add(string|array $key, mixed $value = null): mixed;

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
     * Forget the cached settings for the current group/settable scope.
     * Called automatically after set(), trash(), restore(), and delete().
     */
    public function flush(): bool;
}
