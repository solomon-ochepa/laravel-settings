<?php

declare(strict_types=1);

namespace SolomonOchepa\Settings\Repositories;

use DateInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use SolomonOchepa\Settings\Interfaces\SettingsInterface;
use SolomonOchepa\Settings\Models\Setting;

class SettingsRepository implements SettingsInterface
{
    public bool $flush = false;

    public string|array $group = [];

    protected array $columns = [];

    protected string $cache_key;

    public DateInterval $cache_ttl;

    public mixed $settable = null;

    public function __construct()
    {
        $this->group = config('settings.group.default', 'default');
        $this->columns['name'] = config('settings.columns.name', 'name');
        $this->columns['value'] = config('settings.columns.value', 'value');
        $this->cache_key = config('settings.cache.key', 'settings');
        $this->cache_ttl = config('settings.cache.ttl', DateInterval::createFromDateString('2 hours'));
    }

    /**
     * {@inheritdoc}
     */
    public function group(string|array $name): self
    {
        $this->group = (array) $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function for(null|string|object $settable = null): self
    {
        $this->settable = $settable ?: null;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function user(): self
    {
        return $this->for(Auth::user());
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

        if ($this->flush) {
            Cache::flush();
        }

        if (is_array($this->group) and count($this->group) > 1) {
            $data = collect();

            foreach ($this->group as $group) {
                $data->put($group, (clone $this)->group($group)->all());
            }

            return $data;
        }

        $key = $this->cache_key();

        if (Cache::has($key)) {
            return Cache::get($key);
        }

        $data = $this->query()->pluck($this->columns['value'], $this->columns['name']);

        Cache::add($key, $data, $this->cache_ttl);

        return $data;
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
    public function remember(string $key, mixed $default): mixed
    {
        $settings = $this->all();

        if ($settings->has($key)) {
            return $settings->get($key);
        }

        return $this->set($key, $default);
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
            if ($key === []) {
                return null;
            }

            foreach ($key as $_key => $value) {
                $this->set($_key, $value);
            }

            $this->flush();

            return $this->get(array_key_first($key), Arr::first($key));
        }

        foreach ((array) $this->group as $group) {
            $this->model()->updateOrCreate([
                $this->columns['name'] => $key,
                'group' => $group,
                'settable_type' => $this->settable('type'),
                'settable_id' => $this->settable('id'),
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
        return (bool) Cache::forget($this->cache_key());
    }

    protected function settable(?string $key = null): null|string|array
    {
        $settable = [
            'type' => is_object($this->settable) ? get_class($this->settable) : $this->settable,
            'id' => is_object($this->settable) ? $this->settable->id : null,
        ];

        return $key ? $settable[$key] : $settable;
    }

    /**
     * Get settings cache key.
     *
     * The group/type/id/key components are hashed as a single structured
     * value (rather than concatenated as raw strings) so that a crafted
     * settable string (e.g. "App\Models\User_123") can never collide with
     * the cache key of a real, unrelated (type, id) scope.
     */
    protected function cache_key(?string $key = null): string
    {
        $identity = json_encode([
            (array) $this->group,
            $this->settable('type'),
            $this->settable('id'),
            $key,
        ]);

        return $this->cache_key.'.'.sha1((string) $identity);
    }

    /**
     * Get settings eloquent model.
     *
     * A custom model configured via settings.model must extend the base
     * Setting model.
     */
    protected function model(): Setting
    {
        return app(config('settings.model', Setting::class));
    }

    /**
     * Get the model query builder.
     *
     * @return Builder<Setting>
     */
    protected function query(): Builder
    {
        return $this->model()
            ->when($this->group, fn ($q) => $q->group($this->group))
            ->when(
                $this->settable,
                fn ($q) => $q->for($this->settable),
                fn ($q) => $q->whereNull('settable_type')->whereNull('settable_id'),
            );
    }
}
