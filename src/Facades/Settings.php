<?php

namespace SolomonOchepa\Settings\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for accessing the Settings service.
 *
 * This class provides a static interface to the underlying
 * Settings service, which implements the SettingsInterface.
 *
 * @see \SolomonOchepa\Settings\Interfaces\SettingsInterface
 *
 * @method static self group(string $name): self
 * @method static self for(string|object $settable): self
 * @method static self user(): self
 * @method static Illuminate\Support\Collection all(bool $cached = true): Illuminate\Support\Collection
 * @method static mixed my(string $key, mixed $default = null): mixed
 * @method static mixed get(string $key, mixed $default = null, bool $cached = true): mixed
 * @method static mixed set(string|array $key, mixed $value = null): mixed
 * @method static bool has(string $key): bool
 * @method static bool missing(string $key): bool
 * @method static mixed trash(string $key): mixed
 * @method static mixed restore(string $key): mixed
 * @method static mixed delete(string $key): mixed
 * @method static bool flush(): bool
 */
class Settings extends Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor()
    {
        return 'settings';
    }
}
