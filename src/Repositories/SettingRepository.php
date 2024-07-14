<?php

namespace Oki\Settings\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Oki\Settings\Interfaces\SettingInterface;

class SettingRepository implements SettingInterface
{
    protected string $group = 'default';

    protected array $columns = [];

    protected string $cache_key = 'settings';

    protected ?string $settable_type = null;

    protected mixed $settable_id = null;

    public function __construct()
    {
        $this->columns['name'] = config('settings.columns.name', 'name');
        $this->columns['value'] = config('settings.columns.value', 'value');
    }

    /**
     * {@inheritdoc}
     */
    public function all(bool $cached = true): Collection
    {
        if (! Schema::hasTable(config('settings.table'))) {
            return collect();
        }

        if ($cached) {
            return $this->modelQuery()->pluck($this->columns['value'], $this->columns['name']);
        }

        return Cache::rememberForever($this->cache_key(), function () {
            return $this->modelQuery()->pluck($this->columns['value'], $this->columns['name']);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function my(string $key, mixed $default = null, bool $cached = true): mixed
    {
        $this->user()->get($key, $default);
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
        return app(config('settings.model', '\Oki\Settings\Models\Setting'));
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
        return $this->for(config('settings.user.model', User::class), auth()->id());
    }
}
