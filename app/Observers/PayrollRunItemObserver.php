<?php

namespace App\Observers;

use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Services\AccountingService;
use Illuminate\Support\Facades\Log;

class PayrollRunItemObserver
{
    public function created(PayrollRunItem $item): void
    {
        $itemId = (int) $item->id;
        defer(fn () => $this->syncAccrualById($itemId));
    }

    public function updated(PayrollRunItem $item): void
    {
        $accrualChanged = $item->wasChanged(['net_salary', 'reference_no']);
        $paymentChanged = $item->wasChanged([
            'payment_status',
            'paid_at',
            'payment_reference',
            'net_salary',
        ]);

        if (!$accrualChanged && !$paymentChanged) {
            return;
        }

        $itemId = (int) $item->id;

        defer(function () use ($itemId, $accrualChanged, $paymentChanged) {
            if ($accrualChanged) {
                AccountingService::reverseEvent($this->accrualEventKey($itemId));
                $this->syncAccrualById($itemId);
            }

            if ($paymentChanged) {
                AccountingService::reverseEvent($this->paymentEventKey($itemId));
                $this->syncPaymentById($itemId);
            }
        });
    }

    public function deleted(PayrollRunItem $item): void
    {
        $itemId = (int) $item->id;
        defer(function () use ($itemId) {
            AccountingService::reverseEvent($this->paymentEventKey($itemId));
            AccountingService::reverseEvent($this->accrualEventKey($itemId));
        });
    }

    private function syncAccrualById(int $itemId): void
    {
        $item = PayrollRunItem::query()->with(['payrollRun', 'employee'])->find($itemId);
        if ($item) {
            $this->syncAccrual($item);
        }
    }

    private function syncPaymentById(int $itemId): void
    {
        $item = PayrollRunItem::query()->with(['payrollRun', 'employee'])->find($itemId);
        if ($item) {
            $this->syncPayment($item);
        }
    }

    private function syncAccrual(PayrollRunItem $item): void
    {
        try {
            $run = $item->payrollRun;
            if (!$run || $run->status !== PayrollRun::STATUS_FINALIZED || (float) $item->net_salary <= 0) {
                return;
            }

            $salaryExpenseId = AccountingService::accountId(AccountingService::GL_SALARY_EXPENSE);
            $payrollPayableId = AccountingService::accountId(AccountingService::GL_PAYROLL_PAYABLE);
            if (!$salaryExpenseId || !$payrollPayableId) {
                Log::warning('PayrollRunItemObserver: chart of accounts not seeded yet');
                return;
            }

            AccountingService::postOnce(
                $this->accrualEventKey((int) $item->id),
                PayrollRunItem::class,
                (int) $item->id,
                $salaryExpenseId,
                $payrollPayableId,
                (float) $item->net_salary,
                $run->finalized_at?->format('Y-m-d') ?? $run->period_end->format('Y-m-d'),
                'Payroll accrual – ' . (optional($item->employee)->name ?? "Employee {$item->employee_id}"),
                $item->reference_no ?? "PAY-{$item->id}",
                'payroll_accrual'
            );
        } catch (\Throwable $e) {
            $this->logFailure($item, $e);
        }
    }

    private function syncPayment(PayrollRunItem $item): void
    {
        try {
            $run = $item->payrollRun;
            if (
                !$run
                || $run->status !== PayrollRun::STATUS_FINALIZED
                || $item->payment_status !== PayrollRunItem::PAYMENT_PAID
                || (float) $item->net_salary <= 0
            ) {
                return;
            }

            $payrollPayableId = AccountingService::accountId(AccountingService::GL_PAYROLL_PAYABLE);
            $cashId = AccountingService::accountId(AccountingService::GL_CASH);
            if (!$payrollPayableId || !$cashId) {
                Log::warning('PayrollRunItemObserver: chart of accounts not seeded yet');
                return;
            }

            AccountingService::postOnce(
                $this->paymentEventKey((int) $item->id),
                PayrollRunItem::class,
                (int) $item->id,
                $payrollPayableId,
                $cashId,
                (float) $item->net_salary,
                $item->paid_at?->format('Y-m-d') ?? now()->toDateString(),
                'Payroll payment – ' . (optional($item->employee)->name ?? "Employee {$item->employee_id}"),
                $item->payment_reference ?? $item->reference_no,
                'payroll_payment'
            );
        } catch (\Throwable $e) {
            $this->logFailure($item, $e);
        }
    }

    private function logFailure(PayrollRunItem $item, \Throwable $e): void
    {
        Log::error('PayrollRunItemObserver: accounting post failed', [
            'payroll_run_item_id' => $item->id,
            'error' => $e->getMessage(),
        ]);
    }

    private function accrualEventKey(PayrollRunItem|int $item): string
    {
        $id = $item instanceof PayrollRunItem ? $item->id : $item;

        return "payroll-item:{$id}:accrual";
    }

    private function paymentEventKey(PayrollRunItem|int $item): string
    {
        $id = $item instanceof PayrollRunItem ? $item->id : $item;

        return "payroll-item:{$id}:payment";
    }
}
