<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OperationsDataReset
{
    /** @var list<string> */
    public const TABLES = [
        'purchase_return_items',
        'purchase_returns',
        'purchase_payments',
        'purchase_items',
        'purchases',
        'sales_person_expenses',
        'expenses',
        'material_stock_movements',
        'stock_ledgers',
    ];

    /** @var list<string> */
    private const JOURNAL_TYPES = [
        'purchase',
        'purchase_payment',
        'supplier_refund',
        'purchase_return',
        'expense',
        'sales_person_expense',
        'material_inventory_adjustment',
    ];

    /** @var list<string> */
    private const EVENT_KEY_PREFIXES = [
        'purchase:',
        'purchase-payment:',
        'purchase-return:',
        'expense:',
        'sales-person-expense:',
        'inventory:material-movement:',
    ];

    /**
     * @return array{truncated: list<string>, skipped: list<string>, materials_reset: int, journals_removed: int, postings_removed: int}
     */
    public function reset(): array
    {
        // MySQL TRUNCATE implicit-commits, so this cannot run inside DB::transaction().
        $truncated = [];
        $skipped = [];

        Schema::disableForeignKeyConstraints();

        try {
            foreach (self::TABLES as $table) {
                if (! Schema::hasTable($table)) {
                    $skipped[] = $table;
                    continue;
                }

                DB::table($table)->truncate();
                $truncated[] = $table;
            }

            $materialsReset = 0;
            if (Schema::hasTable('materials')) {
                $materialsReset = DB::table('materials')->update(['quantity' => 0]);
            }

            $ledger = $this->deleteRelatedLedgers();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return [
            'truncated' => $truncated,
            'skipped' => $skipped,
            'materials_reset' => $materialsReset,
            'journals_removed' => $ledger['journals_removed'],
            'postings_removed' => $ledger['postings_removed'],
        ];
    }

    /**
     * @return array{journals_removed: int, postings_removed: int}
     */
    private function deleteRelatedLedgers(): array
    {
        $txnNumbers = [];

        if (Schema::hasTable('accounting_postings')) {
            $postings = DB::table('accounting_postings')->get(['id', 'event_key', 'event_type', 'transaction_number']);
            $ids = [];
            foreach ($postings as $posting) {
                if ($this->isRelatedPosting((string) $posting->event_key, (string) $posting->event_type)) {
                    $ids[] = $posting->id;
                    if ($posting->transaction_number) {
                        $txnNumbers[] = (string) $posting->transaction_number;
                    }
                }
            }
            if ($ids !== []) {
                DB::table('accounting_postings')->whereIn('id', $ids)->delete();
            }
            $postingsRemoved = count($ids);
        } else {
            $postingsRemoved = 0;
        }

        if (! Schema::hasTable('journal_entries')) {
            return [
                'journals_removed' => 0,
                'postings_removed' => $postingsRemoved,
            ];
        }

        $query = DB::table('journal_entries')->where(function ($q) use ($txnNumbers) {
            $q->whereIn('transaction_type', self::JOURNAL_TYPES);
            if ($txnNumbers !== []) {
                $q->orWhereIn('transaction_number', array_values(array_unique($txnNumbers)));
            }
        });

        $journalsRemoved = (clone $query)->count();
        $query->delete();

        return [
            'journals_removed' => $journalsRemoved,
            'postings_removed' => $postingsRemoved,
        ];
    }

    private function isRelatedPosting(string $eventKey, string $eventType): bool
    {
        if (in_array($eventType, self::JOURNAL_TYPES, true)) {
            return true;
        }

        foreach (self::EVENT_KEY_PREFIXES as $prefix) {
            if (str_starts_with($eventKey, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
