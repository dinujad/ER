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

    protected function sessionBusinessId(): int
    {
        $user = Auth::user();

        return (int) (session('business.id') ?? $user?->business_id ?? 0);
    }

    protected function assertBusinessScope(int $resourceBusinessId): void
    {
        $bid = $this->sessionBusinessId();
        if ($bid > 0 && $resourceBusinessId !== $bid) {
            abort(404);
        }
    }
}