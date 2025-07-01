<?php

namespace SolomonOchepa\Settings\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Setting extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['id', 'updated_at', 'deleted_at'];

    public function scopeGroup($query, string|array $name)
    {
        return $query->whereIn('group', (array) $name);
    }

    public function scopeFor($query, string|object $settable)
    {
        return $query
            ->where('settable_type', is_object($settable) ? get_class($settable) : $settable)
            ->where('settable_id', is_object($settable) ? $settable?->id : null);
    }
}
