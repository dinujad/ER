<?php

namespace App\Support;

class ModuleManager
{
    /**
     * Resolve a module asset URL.
     * Format: 'accounting:js/accounting.js'  →  /modules/accounting/js/accounting.js
     */
    public function asset(string $asset): string
    {
        [$module, $path] = array_pad(explode(':', $asset, 2), 2, '');
        $module = strtolower($module);
        return asset("modules/{$module}/{$path}");
    }
}
