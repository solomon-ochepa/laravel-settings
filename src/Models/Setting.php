<?php

namespace SolomonOchepa\Settings\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Setting extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = [
        'id',
        'updated_at',
    ];

    protected function casts()
    {
        return [
            config('settings.columns.value', 'value') => 'json',
        ];
    }

    public function scopeGroup($query, string|array $name)
    {
        return $query->whereIn('group', (array) $name);
    }

    public function scopeFor($query, ?string $settable_type = null, ?string $settable_id = null)
    {
        return $query->whereSettableType($settable_type)->when($settable_id, fn ($query) => $query->whereSettableId($settable_id));
    }
}
