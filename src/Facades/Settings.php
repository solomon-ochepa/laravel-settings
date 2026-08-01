<?php

declare(strict_types=1);

namespace SolomonOchepa\Settings\Facades;

use Illuminate\Support\Facades\Facade;
use SolomonOchepa\Settings\Interfaces\SettingsInterface;

/**
 * Facade for accessing the Settings service.
 *
 * This class provides a static interface to the underlying
 * Settings service, which implements the SettingsInterface.
 *
 * @see SettingsInterface
 *
 * @method static self group(string|array $name): self
 * @method static self for(null|string|object $settable = null): self
 * @method static self user(): self
 * @method static \Illuminate\Support\Collection all(): \Illuminate\Support\Collection
 * @method static mixed my(string $key, mixed $default = null): mixed
 * @method static mixed remember(string $key, mixed $default): mixed
 * @method static mixed get(string $key, mixed $default = null): mixed
 * @method static mixed set(string|array $key, mixed $value = null): mixed
 * @method static mixed add(string|array $key, mixed $value = null): mixed
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
    protected static function getFacadeAccessor(): string
    {
        return 'settings';
    }
}
