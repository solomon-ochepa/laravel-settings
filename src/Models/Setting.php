<?php

namespace SolomonOchepa\Settings\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Setting extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'value',
        'group',
        'settable_type',
        'settable_id',
    ];

    protected $guarded = [
        'id',
        'updated_at',
    ];

    protected function casts()
    {
        return [
            'value' => 'json',
        ];
    }

    public function scopeGroup($query, $name)
    {
        return $query->whereGroup($name);
    }

    public function scopeFor($query, string $settable_type, ?string $settable_id = null)
    {
        return $query->whereSettableType($settable_type)->when($settable_id, fn ($query) => $query->whereSettableId($settable_id));
    }
}
