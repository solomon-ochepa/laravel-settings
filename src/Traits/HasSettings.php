<?php

declare(strict_types=1);

namespace SolomonOchepa\Settings\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use SolomonOchepa\Settings\Models\Setting;

trait HasSettings
{
    public function settings(): MorphMany
    {
        return $this->morphMany(config('settings.model', Setting::class), 'settable');
    }
}
