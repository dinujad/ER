<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\PreviousReturnClaim;
use App\Models\PreviousReturnCredit;
use App\Models\SaleReturn;
use App\Models\SaleReturnClaim;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SaleReturnClaimService
{
    /** Share of each return that becomes claimable credit. */
    public const CLAIMABLE_RATE = 0.30;

    /** Max share of a target invoice that can be paid with return claims. */
    public const TARGET_INVOICE_CLAIM_RATE = 0.30;

    public function claimableFromReturnTotal(float $returnTotal): float
    {
        return round(max(0, $returnTotal) * self::CLAIMABLE_RATE, 2);
    }

    public function maxClaimForInvoiceTotal(float $invoiceTotal): float
    {
        return round(max(0, $invoiceTotal) * self::TARGET_INVOICE_CLAIM_RATE, 2);
    }

    /**
     * @return array{previous: float, system: float, full: float}
     */
    public function breakdown(?Customer $customer): array
    {
        if (!$customer) {
            return ['previous' => 0.0, 'system' => 0.0, 'full' => 0.0];
        }

        $previous = round($customer->previousReturnRemaining(), 2);
        $system = round($customer->systemReturnRemaining(), 2);

        return [
            'previous' => $previous,
            'system' => $system,
            'full' => round($previous + $system, 2),
        ];
    }

    /**
     * @param  array<int, int>  $customerIds
     * @return array<int, array{previous: float, system: float, full: float}>
     */
    public function breakdownForCustomers(array $customerIds): array
    {
        $customerIds = array_values(array_unique(array_map('intval', $customerIds)));
        $empty = ['previous' => 0.0, 'system' => 0.0, 'full' => 0.0];
        $out = [];
        foreach ($customerIds as $id) {
            $out[$id] = $empty;
        }
        if ($customerIds === []) {
            return $out;
        }

        $previousRows = PreviousReturnCredit::query()
            ->whereIn('customer_id', $customerIds)
            ->selectRaw('customer_id, SUM(amount) as amount_sum, SUM(claimed_amount) as claimed_sum')
            ->groupBy('customer_id')
            ->get();
        $systemRows = SaleReturn::query()
            ->whereIn('customer_id', $customerIds)
            ->selectRaw('customer_id, SUM(claimable_amount) as amount_sum, SUM(claimed_amount) as claimed_sum')
            ->groupBy('customer_id')
            ->get();

        foreach ($previousRows as $row) {
            $left = max(0, round((float) $row->amount_sum - (float) $row->claimed_sum, 2));
            $out[(int) $row->customer_id]['previous'] = $left;
        }
        foreach ($systemRows as $row) {
            $left = max(0, round((float) $row->amount_sum - (float) $row->claimed_sum, 2));
            $out[(int) $row->customer_id]['system'] = $left;
        }
        foreach ($out as $id => $row) {
            $out[$id]['full'] = round($row['previous'] + $row['system'], 2);
        }

        return $out;
    }

    public function availableCreditForCustomer(?Customer $customer): float
    {
        return $this->breakdown($customer)['full'];
    }

    public function addPreviousCredit(Customer $customer, float $amount, ?string $note, ?int $userId): PreviousReturnCredit
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new InvalidArgumentException('Previous return amount must be greater than zero.');
        }

        return PreviousReturnCredit::create([
            'customer_id' => $customer->id,
            'amount' => $amount,
            'claimed_amount' => 0,
            'note' => filled($note) ? trim($note) : null,
            'created_by' => $userId,
        ]);
    }

    /**
     * Apply return-claim credit to a target order (invoice issue).
     * FIFO against oldest returns with remaining claimable balance.
     */
    public function applyToOrder(Order $order, float $amount, ?int $userId = null): float
    {
        $amount = round(max(0, $amount), 2);
        if ($amount <= 0) {
            return 0.0;
        }

        if (!$order->customer_id) {
            throw new InvalidArgumentException('Customer required to claim return credit.');
        }

        $invoiceTotal = (float) $order->total_amount;
        $alreadyClaimedOnOrder = (float) ($order->claim_credit_amount ?? 0);
        $maxOnInvoice = $this->maxClaimForInvoiceTotal($invoiceTotal);
        $roomOnInvoice = max(0, round($maxOnInvoice - $alreadyClaimedOnOrder, 2));
        $available = $this->availableCreditForCustomer($order->customer);

        $toApply = min($amount, $roomOnInvoice, $available);
        if ($toApply <= 0) {
            throw new InvalidArgumentException(
                'No claimable return credit available for this invoice (max '
                . number_format($maxOnInvoice, 2)
                . ' = 30% of invoice; customer credit '
                . number_format($available, 2)
                . ').'
            );
        }

        if ($amount > $toApply + 0.0001) {
            throw new InvalidArgumentException(
                'Claim amount cannot exceed Rs ' . number_format($toApply, 2)
                . ' (invoice 30% cap and/or remaining customer return credit).'
            );
        }

        return DB::transaction(function () use ($order, $toApply, $userId) {
            $remaining = $toApply;

            $previousCredits = PreviousReturnCredit::query()
                ->where('customer_id', $order->customer_id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($previousCredits as $credit) {
                if ($remaining <= 0) {
                    break;
                }
                $left = $credit->remaining();
                if ($left <= 0) {
                    continue;
                }
                $take = min($left, $remaining);
                PreviousReturnClaim::create([
                    'customer_id' => $order->customer_id,
                    'previous_return_credit_id' => $credit->id,
                    'order_id' => $order->id,
                    'amount' => $take,
                    'created_by' => $userId,
                ]);
                $credit->increment('claimed_amount', $take);
                $remaining = round($remaining - $take, 2);
            }

            $returns = SaleReturn::query()
                ->where('customer_id', $order->customer_id)
                ->orderBy('return_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($returns as $ret) {
                if ($remaining <= 0) {
                    break;
                }
                $left = max(0, round((float) $ret->claimable_amount - (float) $ret->claimed_amount, 2));
                if ($left <= 0) {
                    continue;
                }
                $take = min($left, $remaining);
                SaleReturnClaim::create([
                    'customer_id' => $order->customer_id,
                    'sale_return_id' => $ret->id,
                    'order_id' => $order->id,
                    'amount' => $take,
                    'created_by' => $userId,
                ]);
                $ret->increment('claimed_amount', $take);
                $remaining = round($remaining - $take, 2);
            }

            if ($remaining > 0.0001) {
                throw new InvalidArgumentException('Could not allocate full claim amount from return credits.');
            }

            $order->increment('claim_credit_amount', $toApply);

            return $toApply;
        });
    }
}
