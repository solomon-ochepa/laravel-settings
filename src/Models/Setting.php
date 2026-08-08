<?php

declare(strict_types=1);

namespace SolomonOchepa\Settings\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use SolomonOchepa\Settings\Interfaces\Settable;

class Setting extends Model
{
    use HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'value',
        'group',
        'settable_type',
        'settable_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'json',
    ];

    /**
     * @param  Builder<Setting>  $query
     * @param  string|array<int, string>  $name
     * @return Builder<Setting>
     */
    public function scopeGroup(Builder $query, string|array $name): Builder
    {
        return $query->whereIn('group', (array) $name);
    }

    /**
     * @param  Builder<Setting>  $query
     * @return Builder<Setting>
     */
    public function scopeFor(Builder $query, string|Model|Settable $settable): Builder
    {
        return $query
            ->where('settable_type', is_object($settable) ? $settable::class : $settable)
            ->where('settable_id', match (true) {
                $settable instanceof Model => $settable->getKey(),
                $settable instanceof Settable => $settable->getSettableKey(),
                default => null,
            });
    }
}
