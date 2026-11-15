<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\SalesRouteRun;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class RouteCashSettlementService
{
    public function ready(): bool
    {
        return Schema::hasTable('sales_route_petty_cash');
    }

    /**
     * @param  Collection<int, SalesRouteRun>  $runs
     * @return Collection<int, object>
     */
    public function forRuns(Collection $runs): Collection
    {
        if ($runs->isEmpty()) {
            return collect();
        }

        if (Schema::hasTable('sales_route_petty_cash')) {
            $runs->loadMissing(['expenses', 'pettyCashEntries']);
        } else {
            $runs->loadMissing('expenses');
            $runs->each(fn (SalesRouteRun $run) => $run->setRelation('pettyCashEntries', collect()));
        }

        $payments = $this->cashPaymentsFor($runs);

        return $runs->mapWithKeys(function (SalesRouteRun $run) use ($payments) {
            return [$run->id => $this->build($run, $payments)];
        });
    }

    public function forRun(SalesRouteRun $run): object
    {
        return $this->forRuns(collect([$run]))->get($run->id);
    }

    /**
     * @param  Collection<int, SalesRouteRun>  $runs
     */
    protected function cashPaymentsFor(Collection $runs): Collection
    {
        $from = $runs->min(fn (SalesRouteRun $run) => $run->started_at);
        $to = $runs->max(fn (SalesRouteRun $run) => $run->endedAt());
        $userIds = $runs->pluck('user_id')->filter()->unique()->values();

        if (!$from || $userIds->isEmpty()) {
            return collect();
        }

        return Payment::query()
            ->customerPayments()
            ->whereRaw('LOWER(TRIM(payment_method)) = ?', ['cash'])
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->whereHas('order', function ($query) use ($userIds) {
                $query->whereIn('user_id', $userIds)
                    ->where('status', '!=', 'cancelled');
            })
            ->with(['order.customer'])
            ->get()
            ->filter(fn (Payment $payment) => $payment->countsTowardPaid() && (float) $payment->amount > 0.009)
            ->values();
    }

    protected function build(SalesRouteRun $run, Collection $payments): object
    {
        $end = $run->endedAt();
        $mine = $payments->filter(function (Payment $payment) use ($run, $end) {
            return (int) ($payment->order?->user_id) === (int) $run->user_id
                && $payment->created_at
                && $run->started_at
                && $payment->created_at->greaterThanOrEqualTo($run->started_at)
                && $payment->created_at->lessThanOrEqualTo($end);
        })->values();

        $collections = $mine
            ->groupBy(fn (Payment $payment) => (int) ($payment->order?->customer_id ?? 0))
            ->map(function (Collection $group) {
                $customer = $group->first()->order?->customer;

                return (object) [
                    'business' => $customer?->display_name ?: '—',
                    'amount' => round((float) $group->sum(fn (Payment $payment) => (float) $payment->amount), 2),
                    'orders' => $group->map(fn (Payment $payment) => $payment->order?->order_number)->filter()->unique()->values(),
                ];
            })
            ->sortByDesc('amount')
            ->values();

        $petty = round((float) $run->pettyCashEntries->sum('amount'), 2);
        $collected = round((float) $collections->sum('amount'), 2);
        $expenses = round((float) $run->expenses->sum('amount'), 2);

        return (object) [
            'petty' => $petty,
            'collected' => $collected,
            'expenses' => $expenses,
            'handover' => round($petty + $collected - $expenses, 2),
            'collections' => $collections,
            'entries' => $run->pettyCashEntries->sortByDesc('id')->values(),
        ];
    }
}
