<?php

namespace App\Services\Accounting;

use App\Models\Accounting\CustomerPaymentAllocation;
use App\Models\Accounting\SupplierPaymentAllocation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentAllocationService
{
    public static function customerUnallocated(int $paymentId): float
    {
        $payment = DB::table('payments')->where('id', $paymentId)->first();
        if (! $payment) {
            return 0.0;
        }
        $paymentAmount = round((float) ($payment->amount ?? 0), 2);
        $allocated = round((float) CustomerPaymentAllocation::where('payment_id', $paymentId)->sum('amount'), 2);

        return round(max(0, $paymentAmount - $allocated), 2);
    }

    public static function supplierUnallocated(int $purchasePaymentId): float
    {
        $payment = DB::table('purchase_payments')->where('id', $purchasePaymentId)->first();
        if (! $payment) {
            return 0.0;
        }
        $paymentAmount = round((float) ($payment->amount ?? 0), 2);
        $allocated = round((float) SupplierPaymentAllocation::where('purchase_payment_id', $purchasePaymentId)->sum('amount'), 2);

        return round(max(0, $paymentAmount - $allocated), 2);
    }

    /**
     * @param  array<int, array{order_id:int, amount:float|string}>  $allocations
     */
    public static function allocateCustomerPayment(int $paymentId, array $allocations, ?int $userId = null): array
    {
        if ($allocations === []) {
            throw new RuntimeException('At least one allocation is required.');
        }

        return DB::transaction(function () use ($paymentId, $allocations, $userId) {
            $payment = DB::table('payments')->where('id', $paymentId)->lockForUpdate()->first();
            if (! $payment) {
                throw new RuntimeException('Payment not found.');
            }

            if (! empty($payment->cheque_status) && $payment->cheque_status === 'bounced') {
                throw new RuntimeException('Cannot allocate a bounced cheque payment.');
            }

            $paymentAmount = round((float) ($payment->amount ?? 0), 2);
            $existing = round((float) CustomerPaymentAllocation::where('payment_id', $paymentId)->sum('amount'), 2);
            $created = [];

            foreach ($allocations as $row) {
                $orderId = (int) ($row['order_id'] ?? 0);
                $amount = round((float) ($row['amount'] ?? 0), 2);
                if ($orderId <= 0 || $amount <= 0) {
                    throw new RuntimeException('Invalid allocation line.');
                }

                $order = DB::table('orders')->where('id', $orderId)->lockForUpdate()->first();
                if (! $order) {
                    throw new RuntimeException("Order {$orderId} not found.");
                }
                if (in_array(($order->status ?? ''), ['cancelled', 'quotation'], true)) {
                    throw new RuntimeException("Cannot allocate to cancelled/quotation order {$orderId}.");
                }

                $orderTotal = round((float) ($order->total_amount ?? $order->final_total ?? $order->total ?? 0), 2);
                $alreadyOnOrder = round((float) CustomerPaymentAllocation::where('order_id', $orderId)->sum('amount'), 2);
                $outstanding = round($orderTotal - $alreadyOnOrder, 2);

                if ($amount - $outstanding > 0.009) {
                    throw new RuntimeException("Allocation exceeds outstanding for order {$orderId} (outstanding {$outstanding}).");
                }

                if (round($existing + $amount, 2) - $paymentAmount > 0.009) {
                    throw new RuntimeException('Allocations exceed payment amount.');
                }

                $alloc = CustomerPaymentAllocation::updateOrCreate(
                    ['payment_id' => $paymentId, 'order_id' => $orderId],
                    ['amount' => $amount, 'allocated_by' => $userId]
                );
                $existing = round($existing + $amount, 2);
                $created[] = $alloc;
            }

            AccountingAuditService::record(
                'ar.payment_allocated',
                null,
                null,
                ['payment_id' => $paymentId, 'count' => count($created)],
                null,
                0,
                $userId
            );

            return $created;
        });
    }

    /**
     * @param  array<int, array{purchase_id:int, amount:float|string}>  $allocations
     */
    public static function allocateSupplierPayment(int $purchasePaymentId, array $allocations, ?int $userId = null): array
    {
        if ($allocations === []) {
            throw new RuntimeException('At least one allocation is required.');
        }

        return DB::transaction(function () use ($purchasePaymentId, $allocations, $userId) {
            $payment = DB::table('purchase_payments')->where('id', $purchasePaymentId)->lockForUpdate()->first();
            if (! $payment) {
                throw new RuntimeException('Supplier payment not found.');
            }

            $paymentAmount = round((float) ($payment->amount ?? 0), 2);
            $existing = round((float) SupplierPaymentAllocation::where('purchase_payment_id', $purchasePaymentId)->sum('amount'), 2);
            $created = [];

            foreach ($allocations as $row) {
                $purchaseId = (int) ($row['purchase_id'] ?? 0);
                $amount = round((float) ($row['amount'] ?? 0), 2);
                if ($purchaseId <= 0 || $amount <= 0) {
                    throw new RuntimeException('Invalid allocation line.');
                }

                $purchase = DB::table('purchases')->where('id', $purchaseId)->lockForUpdate()->first();
                if (! $purchase) {
                    throw new RuntimeException("Purchase {$purchaseId} not found.");
                }

                $billTotal = round((float) ($purchase->final_total ?? $purchase->total ?? 0), 2);
                $already = round((float) SupplierPaymentAllocation::where('purchase_id', $purchaseId)->sum('amount'), 2);
                $outstanding = round($billTotal - $already, 2);

                if ($amount - $outstanding > 0.009) {
                    throw new RuntimeException("Allocation exceeds outstanding for bill {$purchaseId}.");
                }
                if (round($existing + $amount, 2) - $paymentAmount > 0.009) {
                    throw new RuntimeException('Allocations exceed payment amount.');
                }

                $alloc = SupplierPaymentAllocation::updateOrCreate(
                    ['purchase_payment_id' => $purchasePaymentId, 'purchase_id' => $purchaseId],
                    ['amount' => $amount, 'allocated_by' => $userId]
                );
                $existing = round($existing + $amount, 2);
                $created[] = $alloc;
            }

            return $created;
        });
    }
}
