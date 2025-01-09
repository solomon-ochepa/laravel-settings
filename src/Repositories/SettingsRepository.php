<?php

namespace SolomonOchepa\Settings\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use SolomonOchepa\Settings\Interfaces\SettingsInterface;

class SettingsRepository implements SettingsInterface
{
    protected string $group;

    protected array $columns = [];

    protected string $cache_key;

    protected ?string $settable_type = null;

    protected mixed $settable_id = null;

    public function __construct()
    {
        $this->group = config('settings.group.default', 'default');
        $this->columns['name'] = config('settings.columns.name', 'name');
        $this->columns['value'] = config('settings.columns.value', 'value');
        $this->cache_key = config('settings.cache.key', 'settings');
    }

    /**
     * {@inheritdoc}
     */
    public function all(bool $flush = false): Collection
    {
        if (! Schema::hasTable(config('settings.table'))) {
            if (config('app.debug', false)) {
                session()->flash('#settings table not found.');
            }

            return collect();
        }

        if ($flush) {
            Cache::flush();
        }

        if (Cache::missing($this->cache_key())) {
            Cache::add($this->cache_key(), $this->modelQuery()->pluck($this->columns['value'], $this->columns['name']));
        }

        return Cache::get($this->cache_key());
    }

    /**
     * {@inheritdoc}
     */
    public function my(string $key, mixed $default = null): mixed
    {
        return $this->user()->get($key, $default);
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
    public function add(string|array $key, mixed $value = null): mixed
    {
        if (is_array($key)) {
            foreach ($key as $key => $value) {
                $this->add($key, $value);
            }

            return true;
        }

        $setting = $this->model()->firstOrNew([
            $this->columns['name'] => $key,
        ], [
            'group' => $this->group,
            'settable_type' => $this->settable_type,
            'settable_id' => $this->settable_id,
        ]);

        $setting->value = $value;

        $setting->save();

        $this->flush();

        return $value;
    }

    /**
     * @deprecated 1.2.2 use `add(string|array $key, mixed $value = null)`
     */
    public function set(string|array $key, mixed $value = null): mixed
    {
        return $this->add($key, $value);
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
    public function missing(string $key): bool
    {
        return $this->all()->missing($key);
    }

    /**
     * {@inheritdoc}
     */
    public function trash(string $key): mixed
    {
        $trashed = $this->model()->where($this->columns['name'], $key)->delete();

        $this->flush();

        return $trashed;
    }

    /**
     * {@inheritdoc}
     */
    public function restore(string $key): mixed
    {
        $restored = $this->model()->onlyTrashed()->where($this->columns['name'], $key)->restore();

        $this->flush();

        return $restored;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): mixed
    {
        $deleted = $this->model()->onlyTrashed()->where($this->columns['name'], $key)->forceDelete();

        $this->flush();

        return $deleted;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): bool
    {
        return Cache::forget($this->cache_key());
    }

    /**
     * Get settings cache key.
     */
    protected function cache_key(): string
    {
        return $this->cache_key.'.'.$this->group;
    }

    /**
     * Get settings eloquent model.
     *
     * @return Builder
     */
    protected function model()
    {
        return app(config('settings.model', '\SolomonOchepa\Settings\Models\Setting'));
    }

    /**
     * Get the model query builder.
     *
     * @return Builder
     */
    protected function modelQuery()
    {
        return $this->model()
            ->group($this->group)
            ->for($this->settable_type, $this->settable_id);
    }

    /**
     * Set the group name for settings.
     */
    public function group(string $groupName): self
    {
        $this->group = $groupName;

        return $this;
    }

    public function for($settable_type, $settable_id = null): self
    {
        $this->settable_type = $settable_type;
        $this->settable_id = $settable_id;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function user(): self
    {
        return $this->for(config('settings.user.model') ?? get_class(Auth::user()), Auth::id());
    }
}
