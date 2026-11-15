<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Module facade — lightweight replacement for nWidart/laravel-modules Module facade.
 * Only implements asset() which the Accounting module views use.
 */
class Module extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'module.manager';
    }
}
