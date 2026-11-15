<?php

namespace Modules\Accounting\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Auth;

trait RequiresAccountingPermission
{
    protected function requireManage(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->isSystemAdmin() && ! $user->can_access('accounting.manage'))) {
            abort(403);
        }
    }
}