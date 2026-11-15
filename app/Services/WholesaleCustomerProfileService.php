<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class WholesaleCustomerProfileService
{
    public function build(Customer $customer, ?Request $request = null): array
    {
        $query = Order::query()
            ->where('customer_id', $customer->id)
            ->with([
                'payments' => fn ($q) => $q->orderByDesc('payment_date'),
                'salesPerson:id,name,role',
                'items.product',
                'items.variation',
                'saleReturns',
            ])
            ->withSum(['payments as payments_sum_amount' => fn ($q) => $q->effectiveForOrderPaid()], 'amount')
            ->orderByRaw('COALESCE(order_date, created_at) DESC');

        if ($request?->filled('order_no')) {
            $needle = '%' . trim((string) $request->order_no) . '%';
            $query->where('order_number', 'like', $needle);
        }

        if ($request?->filled('order_status') && $request->order_status !== 'all') {
            $query->where('status', $request->order_status);
        }

        $orders = $query->get();

        $activeOrders = $orders->filter(fn ($o) => $o->status !== 'cancelled');
        $cancelledOrders = $orders->filter(fn ($o) => $o->status === 'cancelled');

        $totalOrdered = (float) $activeOrders->sum(fn ($o) => $o->net_total);
        $totalPaid = (float) $activeOrders->sum(fn ($o) => $this->orderPaidAmount($o));
        $totalRefunded = (float) $activeOrders->sum(fn ($o) => $o->total_refunded);
        $totalDue = max(0, round($totalOrdered - $totalPaid + $totalRefunded, 2));

        $paymentLedger = $this->buildPaymentLedger($activeOrders);
        $pendingCheques = $this->buildPendingCheques($activeOrders);
        $pendingChequeAmount = round((float) $pendingCheques->sum(fn ($row) => (float) $row->payment->amount), 2);
        $pendingChequeCount = $pendingCheques->count();

        $saleReturns = \App\Models\SaleReturn::query()
            ->where('customer_id', $customer->id)
            ->with(['order', 'claims.order'])
            ->orderByDesc('return_date')
            ->orderByDesc('id')
            ->get();

        $returnTotal = round((float) $saleReturns->sum('total'), 2);
        $returnClaimable = round((float) $saleReturns->sum('claimable_amount'), 2);
        $returnClaimed = round((float) $saleReturns->sum('claimed_amount'), 2);
        $returnClaimRemaining = round(max(0, $returnClaimable - $returnClaimed), 2);
        $returnCredit = app(SaleReturnClaimService::class)->breakdown($customer);

        $claimsOnOrders = \App\Models\SaleReturnClaim::query()
            ->where('customer_id', $customer->id)
            ->with(['order', 'saleReturn'])
            ->orderByDesc('id')
            ->get();

        $previousReturnCredits = \App\Models\PreviousReturnCredit::query()
            ->where('customer_id', $customer->id)
            ->with('creator:id,name')
            ->orderByDesc('id')
            ->get();

        $previousReturnClaims = \App\Models\PreviousReturnClaim::query()
            ->where('customer_id', $customer->id)
            ->with('order')
            ->orderByDesc('id')
            ->get();

        return [
            'orders' => $orders,
            'activeOrders' => $activeOrders,
            'cancelledOrders' => $cancelledOrders,
            'totalOrdered' => $totalOrdered,
            'totalPaid' => $totalPaid,
            'totalDue' => $totalDue,
            'orderCount' => $activeOrders->count(),
            'paymentLedger' => $paymentLedger,
            'pendingCheques' => $pendingCheques,
            'pendingChequeAmount' => $pendingChequeAmount,
            'pendingChequeCount' => $pendingChequeCount,
            'saleReturns' => $saleReturns,
            'returnTotal' => $returnTotal,
            'returnClaimable' => $returnClaimable,
            'returnClaimed' => $returnClaimed,
            'returnClaimRemaining' => $returnClaimRemaining,
            'returnCredit' => $returnCredit,
            'previousReturnCredits' => $previousReturnCredits,
            'previousReturnClaims' => $previousReturnClaims,
            'claimsOnOrders' => $claimsOnOrders,
        ];
    }

    public function orderPaidAmount(Order $order): float
    {
        if ($order->relationLoaded('payments')) {
            return (float) $order->payments
                ->filter(fn ($p) => !$p->isRefund() && $p->countsTowardPaid())
                ->sum(fn ($p) => $p->effectiveSettlementAmount());
        }

        return (float) $order->paid_amount;
    }

    public function orderDueAmount(Order $order): float
    {
        return max(0, round((float) $order->net_total - $this->orderPaidAmount($order) + (float) $order->total_refunded, 2));
    }

    protected function buildPaymentLedger(Collection $orders): Collection
    {
        return $orders
            ->flatMap(function (Order $order) {
                return $order->payments->map(fn ($payment) => (object) [
                    'payment' => $payment,
                    'order' => $order,
                ]);
            })
            ->sortByDesc(fn ($row) => $row->payment->payment_date?->format('Y-m-d') ?? '')
            ->values();
    }

    /**
     * Cheques given by customer but not yet deposited to our bank.
     */
    protected function buildPendingCheques(Collection $orders): Collection
    {
        return $orders
            ->flatMap(function (Order $order) {
                return $order->payments
                    ->filter(function ($payment) {
                        if ($payment->isRefund() || !$payment->isCheque()) {
                            return false;
                        }
                        $status = $payment->cheque_status;

                        return $status === null || $status === \App\Models\Payment::CHEQUE_RECEIVED;
                    })
                    ->map(fn ($payment) => (object) [
                        'payment' => $payment,
                        'order' => $order,
                    ]);
            })
            ->sortBy(fn ($row) => $row->payment->cheque_date?->format('Y-m-d') ?? $row->payment->payment_date?->format('Y-m-d') ?? '9999')
            ->values();
    }

    public static function paymentMethodLabel(?string $method): string
    {
        return match (strtolower(trim((string) $method))) {
            'bank_transfer' => 'Bank Transfer',
            'cheque' => 'Cheque',
            'card' => 'Card',
            'cash' => 'Cash',
            'transport' => 'Transport',
            default => $method ? ucfirst(str_replace('_', ' ', $method)) : '—',
        };
    }
}
