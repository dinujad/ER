<?php

namespace App\Utils;

class ModuleUtil
{
    public function hasThePermissionInSubscription($business_id, $module): bool
    {
        return true;
    }

    public function getModuleData($key = null)
    {
        return [];
    }

    public function __call($name, $arguments)
    {
        return null;
    }
}
