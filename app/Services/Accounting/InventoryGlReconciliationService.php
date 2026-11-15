<?php

namespace App\Services\Accounting;

use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Diagnostic reconciliation: inventory subledger valuation vs GL inventory control accounts.
 * Read-only — does not mutate stock or journals.
 */
class InventoryGlReconciliationService
{
    /**
     * @return array{
     *   as_of: string,
     *   subledger_total: string,
     *   gl_inventory: string,
     *   gl_raw: string,
     *   gl_wip: string,
     *   gl_fg: string,
     *   gl_total: string,
     *   difference: string,
     *   products: list<array<string,mixed>>,
     *   notes: list<string>
     * }
     */
    public static function reconcile(?string $asOf = null): array
    {
        $asOf = $asOf ?: date('Y-m-d');
        $notes = [];
        $products = [];
        $subledger = 0.0;

        if (Schema::hasTable('products')) {
            $hasCost = Schema::hasColumn('products', 'cost_price');
            $hasStock = Schema::hasColumn('products', 'alert_quantity'); // fallback marker
            $qtyCol = null;
            foreach (['qty_available', 'current_stock', 'stock_qty', 'quantity'] as $c) {
                if (Schema::hasColumn('products', $c)) {
                    $qtyCol = $c;
                    break;
                }
            }

            if (Schema::hasTable('variations') && Schema::hasColumn('variations', 'qty_available')) {
                $rows = DB::table('variations')
                    ->leftJoin('products', 'products.id', '=', 'variations.product_id')
                    ->select([
                        'variations.id as variation_id',
                        'variations.product_id',
                        'products.name as product_name',
                        'variations.qty_available',
                        DB::raw('COALESCE(variations.default_purchase_price, products.cost_price, 0) as unit_cost'),
                    ])
                    ->get();

                foreach ($rows as $row) {
                    $qty = round((float) ($row->qty_available ?? 0), 4);
                    $cost = round((float) ($row->unit_cost ?? 0), 4);
                    $value = round($qty * $cost, 2);
                    $subledger = round($subledger + $value, 2);
                    $products[] = [
                        'product_id' => $row->product_id,
                        'variation_id' => $row->variation_id,
                        'name' => $row->product_name,
                        'qty' => $qty,
                        'unit_cost' => $cost,
                        'value' => number_format($value, 2, '.', ''),
                    ];
                }
            } elseif ($qtyCol && $hasCost) {
                $rows = DB::table('products')->select(['id', 'name', $qtyCol.' as qty', 'cost_price'])->get();
                foreach ($rows as $row) {
                    $qty = round((float) ($row->qty ?? 0), 4);
                    $cost = round((float) ($row->cost_price ?? 0), 4);
                    $value = round($qty * $cost, 2);
                    $subledger = round($subledger + $value, 2);
                    $products[] = [
                        'product_id' => $row->id,
                        'variation_id' => null,
                        'name' => $row->name,
                        'qty' => $qty,
                        'unit_cost' => $cost,
                        'value' => number_format($value, 2, '.', ''),
                    ];
                }
            } else {
                $notes[] = 'Product quantity column not found; subledger valuation limited. Check stock_ledgers / variations.';
            }
        } else {
            $notes[] = 'products table missing.';
        }

        if (Schema::hasTable('stock_ledgers') && $subledger == 0.0) {
            $notes[] = 'stock_ledgers present — prefer movement-based valuation for production diagnostics.';
        }

        $glInventory = self::glClosing(AccountingService::GL_INVENTORY, $asOf);
        $glRaw = self::glClosing(AccountingService::GL_RAW_MATERIALS, $asOf);
        $glWip = self::glClosing(AccountingService::GL_WORK_IN_PROGRESS, $asOf);
        $glFg = self::glClosing(AccountingService::GL_FINISHED_GOODS, $asOf);
        $glTotal = round($glInventory + $glRaw + $glWip + $glFg, 2);

        return [
            'as_of' => $asOf,
            'subledger_total' => number_format($subledger, 2, '.', ''),
            'gl_inventory' => number_format($glInventory, 2, '.', ''),
            'gl_raw' => number_format($glRaw, 2, '.', ''),
            'gl_wip' => number_format($glWip, 2, '.', ''),
            'gl_fg' => number_format($glFg, 2, '.', ''),
            'gl_total' => number_format($glTotal, 2, '.', ''),
            'difference' => number_format(round($glTotal - $subledger, 2), 2, '.', ''),
            'products' => $products,
            'notes' => $notes,
        ];
    }

    protected static function glClosing(string $glCode, string $asOf): float
    {
        $id = AccountingService::accountId($glCode);
        if (! $id) {
            return 0.0;
        }

        return (float) AccountRegisterService::register($id, '1970-01-01', $asOf)['closing'];
    }
}
