<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SalesCommission;
use Carbon\Carbon;
class SalesPersonCommissionService
{
    public function summaryForUser(int $userId): array
    {
        $commissions = SalesCommission::where('user_id', $userId)
            ->orderByDesc('month')
            ->get();

        $pendingTotal = 0;
        $totalEarned = 0;
        $history = [];

        foreach ($commissions as $commission) {
            $totalAmount = $this->computeTotal($userId, $commission->month, $commission);
            $commission->total_amount = $totalAmount;

            if ($commission->status === 'pending') {
                $pendingTotal += $totalAmount;
            } else {
                $totalEarned += $totalAmount;
            }

            $history[] = (object) [
                'month' => $commission->month,
                'month_label' => Carbon::parse($commission->month)->format('F Y'),
                'total_amount' => $totalAmount,
                'status' => $commission->status,
                'paid_at' => $commission->paid_at,
                'payment_method' => $commission->payment_method,
            ];
        }

        return [
            'pendingTotal' => round($pendingTotal, 2),
            'totalEarned' => round($totalEarned, 2),
            'history' => $history,
        ];
    }

    public function forMonth(int $userId, string $monthYm): ?object
    {
        $commission = SalesCommission::where('user_id', $userId)
            ->where('month', $monthYm)
            ->first();

        if (!$commission) {
            return null;
        }

        $breakdown = $this->breakdownWithCommission($userId, $monthYm, $commission);

        return (object) [
            'record' => $commission,
            'month' => $monthYm,
            'month_label' => Carbon::parse($monthYm)->format('F Y'),
            'total_amount' => $breakdown['total'],
            'status' => $commission->status,
            'paid_at' => $commission->paid_at,
            'payment_method' => $commission->payment_method,
            'cash_rate' => (float) ($commission->cash_rate ?? 0),
            'cheque_rate' => (float) ($commission->cheque_rate ?? 0),
            'cash_collection' => $breakdown['cash_collection'],
            'cheque_collection' => $breakdown['cheque_collection'],
            'cash_commission' => $breakdown['cash_commission'],
            'cheque_commission' => $breakdown['cheque_commission'],
        ];
    }

    public function computeTotal(int $userId, string $month, SalesCommission $commission): float
    {
        return $this->breakdownWithCommission($userId, $month, $commission)['total'];
    }

    public function breakdownWithCommission(int $userId, string $monthYm, SalesCommission $commission): array
    {
        $breakdown = $this->collectionBreakdown($userId, $monthYm);
        $breakdown['cash_commission'] = round(
            ($breakdown['cash_collection'] * (float) ($commission->cash_rate ?? 0)) / 100,
            2
        );
        $breakdown['cheque_commission'] = round(
            ($breakdown['cheque_collection'] * (float) ($commission->cheque_rate ?? 0)) / 100,
            2
        );
        $breakdown['total'] = round($breakdown['cash_commission'] + $breakdown['cheque_commission'], 2);

        return $breakdown;
    }

    public function collectionBreakdown(int $userId, string $monthYm): array
    {
        $startDate = Carbon::parse($monthYm)->startOfMonth();
        $endDate = Carbon::parse($monthYm)->endOfMonth();

        $orders = Order::with('payments')
            ->where('user_id', $userId)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('order_date', [$startDate, $endDate])
            ->get();

        $cashCollection = 0;
        $chequeCollection = 0;

        foreach ($orders as $order) {
            $cashCollection += (float) $order->payments
                ->filter(fn ($p) => !$p->isRefund())
                ->whereIn('payment_method', ['cash', 'bank_transfer'])
                ->sum('amount');
            $cashCollection -= (float) $order->payments
                ->filter(fn ($p) => $p->isRefund())
                ->whereIn('payment_method', ['cash', 'bank_transfer'])
                ->sum('amount');
            $chequeCollection += (float) $order->payments
                ->filter(fn ($p) => !$p->isRefund())
                ->where('payment_method', 'cheque')
                ->sum('amount');
            $chequeCollection -= (float) $order->payments
                ->filter(fn ($p) => $p->isRefund())
                ->where('payment_method', 'cheque')
                ->sum('amount');
        }

        return [
            'cash_collection' => round(max(0, $cashCollection), 2),
            'cheque_collection' => round(max(0, $chequeCollection), 2),
        ];
    }
}
