<?php

namespace Oki\Settings\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Oki\Settings\Interfaces\SettingInterface;

class SettingRepository implements SettingInterface
{
    /**
     * Group name.
     */
    protected string $settingsGroupName = 'default';

    /**
     * Cache key.
     */
    protected string $settingsCacheKey = 'app_settings';

    protected string $settable_type = '';

    protected mixed $settable_id = '';

    /**
     * {@inheritdoc}
     */
    public function all(bool $cached = true): Collection
    {
        if (! Schema::hasTable(config('settings.table'))) {
            return collect();
        }

        if ($cached) {
            return $this->modelQuery()->pluck('value', 'name');
        }

        return Cache::rememberForever($this->getSettingsCacheKey(), function () {
            return $this->modelQuery()->pluck('value', 'name');
        });
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null, bool $cached = true): mixed
    {
        return $this->all($cached)->get($key, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string|array $key, mixed $value = null, ?string $settable_type = null, $settable_id = null): mixed
    {
        if (is_array($key)) {
            foreach ($key as $key => $value) {
                $this->set($key, $value, $settable_type, $settable_id);
            }

            return true;
        }

        $setting = $this->getSettingModel()->firstOrNew([
            'name' => $key,
            'group' => $this->settingsGroupName,
            // for
            'settable_type' => $this->settable_type ?? $settable_type,
            'settable_id' => $this->settable_id ?? $settable_id,
        ]);

        $setting->value = $value;

        $setting->save();

        $this->flush();

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return $this->all()->has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function trash(string $key): mixed
    {
        $trashed = $this->getSettingModel()->where('name', $key)->delete();

        $this->flush();

        return $trashed;
    }

    /**
     * {@inheritdoc}
     */
    public function restore(string $key): mixed
    {
        $restored = $this->getSettingModel()->onlyTrashed()->where('name', $key)->restore();

        $this->flush();

        return $restored;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): mixed
    {
        $deleted = $this->getSettingModel()->onlyTrashed()->where('name', $key)->forceDelete();

        $this->flush();

        return $deleted;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): bool
    {
        return Cache::forget($this->getSettingsCacheKey());
    }

    /**
     * Get settings cache key.
     */
    protected function getSettingsCacheKey(): string
    {
        return $this->settingsCacheKey.'.'.$this->settingsGroupName;
    }

    /**
     * Get settings eloquent model.
     *
     * @return Builder
     */
    protected function getSettingModel()
    {
        return app('\Oki\Settings\Models\Setting');
    }

    /**
     * Get the model query builder.
     *
     * @return Builder
     */
    protected function modelQuery()
    {
        return $this->getSettingModel()
            ->group($this->settingsGroupName)
            ->for($this->settable_type, $this->settable_id);
    }

    /**
     * Set the group name for settings.
     */
    public function group(string $groupName): self
    {
        $this->settingsGroupName = $groupName;

        return $this;
    }

    public function for($settable_type, $settable_id = null): self
    {
        $this->settable_type = $settable_type;
        $this->settable_id = $settable_id;

        return $this;
    }
}
