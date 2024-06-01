<?php

namespace Oki\Settings\Facades;

use Illuminate\Support\Facades\Facade;

class Settings extends Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor()
    {
        return 'Oki\Settings\Interfaces\SettingInterface';
    }
}
