<?php

namespace SolomonOchepa\Settings\Traits;

use SolomonOchepa\Settings\Models\Setting;

trait HasSettings
{
    public function settings()
    {
        return $this->morphMany(config('settings.model', Setting::class), 'settingable');
    }
}
