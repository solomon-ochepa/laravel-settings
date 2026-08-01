<?php

declare(strict_types=1);

namespace SolomonOchepa\Settings\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function scopeGroup(Builder $query, string|array $name): Builder
    {
        return $query->whereIn('group', (array) $name);
    }

    public function scopeFor(Builder $query, string|object $settable): Builder
    {
        return $query
            ->where('settable_type', is_object($settable) ? get_class($settable) : $settable)
            ->where('settable_id', is_object($settable) ? $settable->id : null);
    }
}
