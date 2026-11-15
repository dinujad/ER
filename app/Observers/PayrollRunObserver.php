<?php

namespace App\Observers;

use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Services\AccountingService;
use Illuminate\Support\Facades\Log;

class PayrollRunObserver
{
    public function created(PayrollRun $run): void
    {
        if ($run->status === PayrollRun::STATUS_FINALIZED) {
            $runId = (int) $run->id;
            defer(fn () => $this->postRunById($runId));
        }
    }

    public function updated(PayrollRun $run): void
    {
        if (!$run->wasChanged('status')) {
            return;
        }

        $runId = (int) $run->id;
        $wasFinalized = $run->getOriginal('status') === PayrollRun::STATUS_FINALIZED;
        $isFinalized = $run->status === PayrollRun::STATUS_FINALIZED;

        defer(function () use ($runId, $wasFinalized, $isFinalized) {
            if ($wasFinalized && !$isFinalized) {
                $this->reverseRunById($runId);
                return;
            }

            if (!$wasFinalized && $isFinalized) {
                $this->postRunById($runId);
            }
        });
    }

    public function backfill(PayrollRun $run): void
    {
        if ($run->status === PayrollRun::STATUS_FINALIZED) {
            $this->postRunById((int) $run->id);
        }
    }

    public function deleting(PayrollRun $run): void
    {
        $itemIds = $run->items()->pluck('id')->map(fn ($id) => (int) $id)->all();

        defer(function () use ($itemIds) {
            foreach ($itemIds as $itemId) {
                AccountingService::reverseEvent($this->paymentEventKey($itemId));
                AccountingService::reverseEvent($this->accrualEventKey($itemId));
            }
        });
    }

    private function postRunById(int $runId): void
    {
        $run = PayrollRun::query()->with('items.employee')->find($runId);
        if ($run) {
            $this->postRun($run);
        }
    }

    private function reverseRunById(int $runId): void
    {
        $run = PayrollRun::query()->with('items')->find($runId);
        if ($run) {
            $this->reverseRun($run);
        }
    }

    private function postRun(PayrollRun $run): void
    {
        try {
            $salaryExpenseId = AccountingService::accountId(AccountingService::GL_SALARY_EXPENSE);
            $payrollPayableId = AccountingService::accountId(AccountingService::GL_PAYROLL_PAYABLE);
            $cashId = AccountingService::accountId(AccountingService::GL_CASH);

            if (!$salaryExpenseId || !$payrollPayableId) {
                Log::warning('PayrollRunObserver: chart of accounts not seeded yet');
                return;
            }

            $run->loadMissing('items.employee');
            $date = $run->finalized_at?->format('Y-m-d')
                ?? $run->period_end?->format('Y-m-d')
                ?? now()->toDateString();

            foreach ($run->items as $item) {
                if ((float) $item->net_salary <= 0) {
                    continue;
                }

                $employeeName = optional($item->employee)->name ?? "Employee {$item->employee_id}";

                AccountingService::postOnce(
                    $this->accrualEventKey($item),
                    PayrollRunItem::class,
                    (int) $item->id,
                    $salaryExpenseId,
                    $payrollPayableId,
                    (float) $item->net_salary,
                    $date,
                    "Payroll accrual – {$employeeName}",
                    $item->reference_no ?? "PAY-{$item->id}",
                    'payroll_accrual'
                );

                if ($item->payment_status === PayrollRunItem::PAYMENT_PAID && $cashId) {
                    AccountingService::postOnce(
                        $this->paymentEventKey($item),
                        PayrollRunItem::class,
                        (int) $item->id,
                        $payrollPayableId,
                        $cashId,
                        (float) $item->net_salary,
                        $item->paid_at?->format('Y-m-d') ?? $date,
                        "Payroll payment – {$employeeName}",
                        $item->payment_reference ?? $item->reference_no,
                        'payroll_payment'
                    );
                }
            }
        } catch (\Throwable $e) {
            Log::error('PayrollRunObserver: accounting post failed', [
                'payroll_run_id' => $run->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function reverseRun(PayrollRun $run): void
    {
        $run->loadMissing('items');

        foreach ($run->items as $item) {
            AccountingService::reverseEvent($this->paymentEventKey((int) $item->id));
            AccountingService::reverseEvent($this->accrualEventKey((int) $item->id));
        }
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
