<?php

namespace App\Services\Accounting;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ChartOfAccountHierarchyService
{
    /**
     * Validate parent assignment. Throws on invalid hierarchy.
     */
    public static function assertValidParent(?int $accountId, ?int $parentId, ?int $businessId = null): void
    {
        if ($parentId === null || $parentId === 0) {
            return;
        }

        if ($accountId !== null && $accountId === $parentId) {
            throw new RuntimeException('An account cannot be its own parent.');
        }

        if (! Schema::hasTable('chart_of_accounts') || ! Schema::hasColumn('chart_of_accounts', 'parent_id')) {
            return;
        }

        $parent = DB::table('chart_of_accounts')->where('id', $parentId)->first();
        if (! $parent) {
            throw new RuntimeException('Parent account does not exist.');
        }

        if ($businessId !== null && Schema::hasColumn('chart_of_accounts', 'business_id')) {
            $parentBiz = (int) ($parent->business_id ?? 0);
            // Shared COA (0) may parent business accounts; different non-zero businesses cannot.
            if ($parentBiz !== 0 && $parentBiz !== (int) $businessId) {
                throw new RuntimeException('Parent account belongs to a different business.');
            }
        }

        if ($accountId !== null && self::wouldCreateCycle($accountId, $parentId)) {
            throw new RuntimeException('Parent assignment would create a circular account hierarchy.');
        }
    }

    /**
     * Walk ancestors of $parentId; if $accountId appears, cycle.
     */
    public static function wouldCreateCycle(int $accountId, int $parentId): bool
    {
        $visited = [];
        $current = $parentId;
        $guard = 0;

        while ($current && $guard < 500) {
            if ($current === $accountId) {
                return true;
            }
            if (isset($visited[$current])) {
                return true; // existing cycle in ancestors
            }
            $visited[$current] = true;
            $current = (int) (DB::table('chart_of_accounts')->where('id', $current)->value('parent_id') ?: 0);
            $guard++;
        }

        return false;
    }

    public static function assertUniqueGlCode(string $glCode, ?int $businessId, ?int $ignoreId = null): void
    {
        if (! Schema::hasTable('chart_of_accounts')) {
            return;
        }

        $q = DB::table('chart_of_accounts')->where('gl_code', $glCode);
        if (Schema::hasColumn('chart_of_accounts', 'business_id') && $businessId !== null) {
            $q->where('business_id', $businessId);
        }
        if ($ignoreId) {
            $q->where('id', '!=', $ignoreId);
        }
        if ($q->exists()) {
            throw new RuntimeException("GL code {$glCode} already exists in this business scope.");
        }
    }
}
