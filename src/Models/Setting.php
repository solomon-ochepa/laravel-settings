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
        return $query->whereGroup($name);
    }

    public function scopeFor($query, ?string $settable_type = null, ?string $settable_id = null)
    {
        return $query->whereSettableType($settable_type)->whereSettableId($settable_id);
    }
}
