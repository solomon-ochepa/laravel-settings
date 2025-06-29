<?php

namespace SolomonOchepa\Settings\Repositories;

use DateInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use SolomonOchepa\Settings\Interfaces\SettingsInterface;

class SettingsRepository implements SettingsInterface
{
    public bool $flush = false;

    public string|array $group = [];

    protected array $columns = [];

    protected string $cache_key;

    public DateInterval $cache_ttl;

    public ?string $settable_type = null;

    public mixed $settable_id = null;

    public function __construct()
    {
        $this->group = config('settings.group.default', 'default');
        $this->columns['name'] = config('settings.columns.name', 'name');
        $this->columns['value'] = config('settings.columns.value', 'value');
        $this->cache_key = config('settings.cache.key', 'settings');
        $this->cache_ttl = config('settings.cache.ttl', now()->addHours(24));
    }

    /**
     * {@inheritdoc}
     */
    public function group(null|string|array $name = null): self|string|array
    {
        if (! $name) {
            return $this->group;
        }

        $this->group = (array) $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function for(string $settable_type, ?string $settable_id = null): self
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

    /**
     * {@inheritdoc}
     */
    public function all(): Collection
    {
        if (! Schema::hasTable(config('settings.table'))) {
            if (config('app.debug', false)) {
                session()->flash('#settings table not found.');
            }

            return collect();
        }

        if (! config('settings.cache.enable')) {
            return $this->query()->pluck($this->columns['value'], $this->columns['name']);
        }

        if ($this->flush) {
            Cache::flush();
        }

        if (Cache::missing($this->cache_key())) {
            Cache::add($this->cache_key(), $this->query()->pluck($this->columns['value'], $this->columns['name']), $this->cache_ttl);
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
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()->get($key, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string|array $key, mixed $value = null): mixed
    {
        if (is_array($key)) {
            foreach ($key as $_key => $value) {
                $this->add($_key, $value);
            }

            $this->flush();

            return $this->get(array_key_first($key), Arr::first($key));
        }

        foreach ((array) $this->group as $group) {
            $this->model()->updateOrCreate([
                $this->columns['name'] => $key,
                'group' => $group,
                'settable_type' => $this->settable_type,
                'settable_id' => $this->settable_id,
            ], [
                $this->columns['value'] => $value,
            ]);
        }

        $this->flush();

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function add(string|array $key, mixed $value = null): mixed
    {
        return $this->set($key, $value);
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
        return ! $this->all()->has($key);
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
        return config('settings.cache.enable') ? (bool) Cache::forget($this->cache_key()) : true;
    }

    /**
     * Get settings cache key.
     */
    protected function cache_key(?string $key = null): string
    {
        return $this->cache_key.($this->group ? '.'.implode('_', (array) $this->group) : '').($key ? '.'.$key : '').'_';
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
    protected function query()
    {
        return $this->model()->group($this->group)->for($this->settable_type, $this->settable_id);
    }
}
