<?php

namespace SolomonOchepa\Settings\Facades;

use Illuminate\Support\Facades\Facade;

class Settings extends Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor()
    {
        return 'SolomonOchepa\Settings\Interfaces\SettingInterface';
    }
}
