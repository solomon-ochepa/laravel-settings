<?php

declare(strict_types=1);

namespace SolomonOchepa\Settings\Repositories;

use DateInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use SolomonOchepa\Settings\Interfaces\Settable;
use SolomonOchepa\Settings\Interfaces\SettingsInterface;
use SolomonOchepa\Settings\Models\Setting;

class SettingsRepository implements SettingsInterface
{
    public bool $flush = false;

    /** @var string|array<int, string> */
    public string|array $group = [];

    /** @var array<string, string> */
    protected array $columns = [];

    protected string $cache_key;

    public DateInterval $cache_ttl;

    public null|string|Model|Settable $settable = null;

    public function __construct()
    {
        $group = config('settings.group.default', 'default');
        $this->group = match (true) {
            is_string($group) => $group,
            is_array($group) => array_values(array_map(strval(...), array_filter($group, is_scalar(...)))),
            default => 'default',
        };

        $this->columns['name'] = Config::string('settings.columns.name', 'name');
        $this->columns['value'] = Config::string('settings.columns.value', 'value');
        $this->cache_key = Config::string('settings.cache.key', 'settings');

        $default_ttl = DateInterval::createFromDateString('2 hours');
        $cache_ttl = config('settings.cache.ttl', $default_ttl);
        $this->cache_ttl = $cache_ttl instanceof DateInterval ? $cache_ttl : $default_ttl;
    }

    /**
     * {@inheritdoc}
     *
     * @param  string|array<int, string>  $name
     */
    public function group(string|array $name): self
    {
        $this->group = (array) $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function for(null|string|Model|Settable $settable = null): self
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
     *
     * @return Collection<string, mixed>
     */
    public function all(): Collection
    {
        if (! Schema::hasTable(Config::string('settings.table', 'settings'))) {
            if (Config::boolean('app.debug', false)) {
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

        $cached = Cache::get($key);

        if ($cached instanceof Collection) {
            return $cached;
        }

        $data = $this->query()->pluck($this->columns['value'], $this->columns['name']);

        Cache::put($key, $data, $this->cache_ttl);

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
     *
     * @param  string|array<string, mixed>  $key
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
     *
     * @param  string|array<string, mixed>  $key
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
    public function trash(string $key): int
    {
        $trashed = $this->model()->where($this->columns['name'], $key)->delete();

        $this->flush();

        return is_int($trashed) ? $trashed : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function restore(string $key): int
    {
        $restored = $this->model()->onlyTrashed()->where($this->columns['name'], $key)->restore();

        $this->flush();

        return $restored;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): int
    {
        $deleted = $this->model()->onlyTrashed()->where($this->columns['name'], $key)->forceDelete();

        $this->flush();

        return is_int($deleted) ? $deleted : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): bool
    {
        return (bool) Cache::forget($this->cache_key());
    }

    /**
     * @return array{type: ?string, id: null|int|string}
     */
    protected function settable(?string $key = null): null|int|string|array
    {
        $settable = $this->settable;

        $type = is_object($settable) ? $settable::class : $settable;
        $rawId = match (true) {
            $settable instanceof Model => $settable->getKey(),
            $settable instanceof Settable => $settable->getSettableKey(),
            default => null,
        };
        $id = is_int($rawId) || is_string($rawId) ? $rawId : null;

        return match ($key) {
            'type' => $type,
            'id' => $id,
            default => ['type' => $type, 'id' => $id],
        };
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
        return app(Config::string('settings.model', Setting::class));
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
                fn ($q, $settable) => $q->for($settable),
                fn ($q) => $q->whereNull('settable_type')->whereNull('settable_id'),
            );
    }
}
