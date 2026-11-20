<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\SalesAccountingService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    public function created(Order $order): void
    {
        if (!$this->isRecognized($order->status)) {
            return;
        }

        $orderId = $order->getKey();

        defer(function () use ($orderId) {
            $this->run(
                fn () => app(SalesAccountingService::class)->synchronizeOrderById($orderId),
                $orderId
            );
        }, "sales-accounting-order-{$orderId}");
    }

    public function updated(Order $order): void
    {
        $wasRecognized = $this->isRecognized((string) $order->getOriginal('status'));
        $isRecognized = $this->isRecognized($order->status);

        $orderId = $order->getKey();

        if ($wasRecognized && !$isRecognized) {
            defer(function () use ($orderId) {
                $this->run(
                    fn () => app(SalesAccountingService::class)->reverseOrder($orderId),
                    $orderId
                );
            }, "sales-accounting-order-{$orderId}");

            return;
        }

        if (!$isRecognized || !$order->wasChanged([
            'status',
            'total_amount',
            'invoice_sscl_amount',
            'invoice_goods_amount',
            'invoice_vat_amount',
            'order_date',
            'invoice_number',
        ])) {
            return;
        }

        $replaceExisting = $wasRecognized;

        defer(function () use ($orderId, $replaceExisting) {
            $this->run(
                fn () => app(SalesAccountingService::class)->synchronizeOrderById($orderId, $replaceExisting),
                $orderId
            );
        }, "sales-accounting-order-{$orderId}");
    }

    public function deleting(Order $order): void
    {
        $this->run(function () use ($order) {
            $accounting = app(SalesAccountingService::class);

            $order->loadMissing('payments');
            foreach ($order->payments as $payment) {
                $accounting->reversePayment($payment);
            }

            $accounting->reverseOrder($order->getKey());
        }, $order->getKey());
    }

    private function isRecognized(string $status): bool
    {
        return in_array($status, [
            Order::STATUS_COMPLETED,
            Order::STATUS_PAID,
            Order::STATUS_CREDIT,
        ], true);
    }

    private function run(callable $callback, int $orderId): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            Log::error('OrderObserver: sales accounting failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
