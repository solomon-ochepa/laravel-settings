<?php

declare(strict_types=1);

namespace SolomonOchepa\Settings\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface SettingsInterface
{
    /**
     * Scope settings to one or more groups.
     *
     * Passing multiple groups makes `all()` return a collection keyed by
     * group name instead of a flat key/value collection.
     *
     * @param  string|array<int, string>  $name  One or more group names to scope to.
     */
    public function group(string|array $name): self;

    /**
     * Scope settings to a specific entity, e.g. an Eloquent model instance,
     * or any object implementing Settable.
     *
     * Passing a falsy value (null, empty string, etc.) clears the scope so
     * subsequent calls read/write global, unscoped settings.
     *
     * @param  null|string|Model|Settable  $settable  The entity, its class-string, or a falsy value to clear the scope.
     */
    public function for(null|string|Model|Settable $settable = null): self;

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
     *
     * @return Collection<string, mixed>
     */
    public function all(): Collection;

    /**
     * Get a setting scoped to the currently authenticated user.
     *
     * @param  string  $key  The setting name to look up.
     * @param  mixed  $default  Value (or Closure resolving to one) returned when the key is missing.
     */
    public function my(string $key, mixed $default = null): mixed;

    /**
     * Get a setting from storage by key, or set and return $default if the
     * key does not already exist. An existing falsy value (null, false, 0,
     * '') is treated as present and is returned as-is, not overwritten.
     *
     * @param  string  $key  The setting name to look up.
     * @param  mixed  $default  Value (or Closure resolving to one) stored and returned when the key does not yet exist.
     */
    public function remember(string $key, mixed $default): mixed;

    /**
     * Get a setting from storage by key.
     *
     * @param  string  $key  The setting name to look up.
     * @param  mixed  $default  Value (or Closure resolving to one) returned when the key is missing.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Save a setting in storage and return the value.
     *
     * @param  string|array<string, mixed>  $key  A single setting name, or an array of name => value pairs to save at once.
     * @param  mixed  $value  The value to store; ignored when $key is an array.
     */
    public function set(string|array $key, mixed $value = null): mixed;

    /**
     * Alias for set().
     *
     * @param  string|array<string, mixed>  $key  A single setting name, or an array of name => value pairs to save at once.
     * @param  mixed  $value  The value to store; ignored when $key is an array.
     */
    public function add(string|array $key, mixed $value = null): mixed;

    /**
     * Check if a setting exists.
     *
     * @param  string  $key  The setting name to check.
     */
    public function has(string $key): bool;

    /**
     * Check if a setting is missing.
     *
     * @param  string  $key  The setting name to check.
     */
    public function missing(string $key): bool;

    /**
     * Trash a setting from storage.
     *
     * @param  string  $key  The setting name to trash.
     * @return int The number of affected rows.
     */
    public function trash(string $key): int;

    /**
     * Restore a setting from storage.
     *
     * @param  string  $key  The setting name to restore.
     * @return int The number of affected rows.
     */
    public function restore(string $key): int;

    /**
     * Permanently delete a setting from storage.
     *
     * @param  string  $key  The setting name to permanently delete.
     * @return int The number of affected rows.
     */
    public function delete(string $key): int;

    /**
     * Forget the cached settings for the current group/settable scope.
     * Called automatically after set(), trash(), restore(), and delete().
     *
     * @return bool Whether a cache entry existed and was removed.
     */
    public function flush(): bool;
}
