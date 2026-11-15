<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SalesPersonAssignmentService
{
    /**
     * @return array{customers: int, orders: int}
     */
    public function countsFor(User $salesPerson): array
    {
        return [
            'customers' => Customer::where('added_by', $salesPerson->id)->count(),
            'orders' => Order::where('user_id', $salesPerson->id)->count(),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, Customer>
     */
    public function assignedCustomers(User $salesPerson)
    {
        return Customer::query()
            ->where('added_by', $salesPerson->id)
            ->withCount(['orders as sales_orders_count' => fn ($q) => $q->where('user_id', $salesPerson->id)])
            ->orderBy('name')
            ->get(['id', 'name', 'business_name', 'phone']);
    }

    /**
     * Orders assigned to this sales person with no customer link.
     */
    public function orphanOrderCount(User $salesPerson): int
    {
        return Order::query()
            ->where('user_id', $salesPerson->id)
            ->whereNull('customer_id')
            ->count();
    }

    /**
     * Move selected customers and their orders from one sales rep to another.
     *
     * @param  array<int, int|string>  $customerIds
     * @return array{customers: int, orders: int, orphan_orders: int}
     */
    public function transferCustomers(User $from, User $to, array $customerIds, bool $moveOrphanOrders = false): array
    {
        if ($from->id === $to->id) {
            return ['customers' => 0, 'orders' => 0, 'orphan_orders' => 0];
        }

        $customerIds = Customer::query()
            ->where('added_by', $from->id)
            ->whereIn('id', $customerIds)
            ->pluck('id')
            ->all();

        if ($customerIds === []) {
            return ['customers' => 0, 'orders' => 0, 'orphan_orders' => 0];
        }

        return DB::transaction(function () use ($from, $to, $customerIds, $moveOrphanOrders) {
            $customers = Customer::query()
                ->whereIn('id', $customerIds)
                ->where('added_by', $from->id)
                ->update(['added_by' => $to->id]);

            $orders = Order::query()
                ->whereIn('customer_id', $customerIds)
                ->where('user_id', $from->id)
                ->update(['user_id' => $to->id]);

            $orphanOrders = 0;
            if ($moveOrphanOrders) {
                $orphanOrders = Order::query()
                    ->where('user_id', $from->id)
                    ->whereNull('customer_id')
                    ->update(['user_id' => $to->id]);
            }

            return [
                'customers' => $customers,
                'orders' => $orders,
                'orphan_orders' => $orphanOrders,
            ];
        });
    }

    /**
     * Move all customers and orders from one sales rep to another.
     *
     * @return array{customers: int, orders: int}
     */
    public function transferAll(User $from, User $to): array
    {
        if ($from->id === $to->id) {
            return ['customers' => 0, 'orders' => 0];
        }

        return DB::transaction(function () use ($from, $to) {
            $customers = Customer::where('added_by', $from->id)->update(['added_by' => $to->id]);
            $orders = Order::where('user_id', $from->id)->update(['user_id' => $to->id]);

            return [
                'customers' => $customers,
                'orders' => $orders,
            ];
        });
    }

    public function assertSalesPerson(User $user): void
    {
        if ($user->role !== 'sales_person') {
            abort(404);
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function otherSalesPersons(int $excludeId)
    {
        return User::where('role', 'sales_person')
            ->where('id', '!=', $excludeId)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
