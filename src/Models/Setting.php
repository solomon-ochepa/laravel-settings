<?php

namespace Oki\Settings\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Setting extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'settings';

    protected $guarded = ['updated_at', 'id'];

    public function scopeGroup($query, $groupName)
    {
        return $query->whereGroup($groupName);
    }

    public function scopeFor($query, $settable_type, $settable_id)
    {
        return $query->whereSettableType($settable_type)->whereSettableId($settable_id);
    }
}
