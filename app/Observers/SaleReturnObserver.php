<?php

namespace App\Observers;

use App\Models\SaleReturn;
use App\Services\SalesAccountingService;
use Illuminate\Support\Facades\Log;

class SaleReturnObserver
{
    public function created(SaleReturn $saleReturn): void
    {
        if ($saleReturn->status !== SaleReturn::STATUS_CONFIRMED || (float) $saleReturn->total <= 0) {
            return;
        }

        $saleReturnId = $saleReturn->getKey();

        defer(function () use ($saleReturnId) {
            $this->run(
                fn () => app(SalesAccountingService::class)->synchronizeSaleReturnById($saleReturnId),
                $saleReturnId
            );
        }, "sales-accounting-return-{$saleReturnId}");
    }

    public function updated(SaleReturn $saleReturn): void
    {
        $wasConfirmed = $saleReturn->getOriginal('status') === SaleReturn::STATUS_CONFIRMED;
        $isConfirmed = $saleReturn->status === SaleReturn::STATUS_CONFIRMED;

        $saleReturnId = $saleReturn->getKey();

        if ($wasConfirmed && !$isConfirmed) {
            defer(function () use ($saleReturnId) {
                $this->run(
                    fn () => app(SalesAccountingService::class)->reverseSaleReturn($saleReturnId),
                    $saleReturnId
                );
            }, "sales-accounting-return-{$saleReturnId}");

            return;
        }

        if (
            !$isConfirmed
            || !$saleReturn->wasChanged(['status', 'total', 'return_date'])
        ) {
            return;
        }

        $replaceExisting = $wasConfirmed;

        defer(function () use ($saleReturnId, $replaceExisting) {
            $this->run(
                fn () => app(SalesAccountingService::class)->synchronizeSaleReturnById($saleReturnId, $replaceExisting),
                $saleReturnId
            );
        }, "sales-accounting-return-{$saleReturnId}");
    }

    public function deleted(SaleReturn $saleReturn): void
    {
        $this->run(
            fn () => app(SalesAccountingService::class)->reverseSaleReturn($saleReturn->getKey()),
            $saleReturn->getKey()
        );
    }

    private function run(callable $callback, int $saleReturnId): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            Log::error('SaleReturnObserver: sales accounting failed', [
                'sale_return_id' => $saleReturnId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
