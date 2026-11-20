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

    /**
     * Phase 6.6 — action-level permission (void, credit note, approve, recode …).
     * System admins always pass; in non-strict mode "accounting.manage" also covers write actions.
     */
    protected function requireAccountingAction(string $permission, bool $isWrite = true): void
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }
        if ($user->isSystemAdmin() || $user->can_access($permission)) {
            return;
        }
        if (! config('dermas.strict_accounting_permissions')) {
            $fallback = $isWrite ? 'accounting.manage' : 'accounting.view';
            if ($user->can_access($fallback)) {
                return;
            }
        }
        abort(403, "You don't have permission for this accounting action ({$permission}).");
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