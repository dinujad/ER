<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\ChequePaymentLifecycleService;
use App\Services\ChequePaymentNotifier;
use App\Services\SalesAccountingService;
use Illuminate\Support\Facades\Log;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        $paymentId = (int) $payment->getKey();

        try {
            $fresh = Payment::query()->with('order')->find($paymentId);
            if ($fresh) {
                app(SalesAccountingService::class)->synchronizePayment($fresh);
            }
        } catch (\Throwable $e) {
            Log::error('PaymentObserver accounting failed: '.$e->getMessage(), [
                'payment_id' => $paymentId,
            ]);
        }

        if ($payment->entry_type === Payment::ENTRY_REFUND) {
            return;
        }

        if (strtolower((string) $payment->payment_method) !== 'cheque') {
            return;
        }

        $id = $payment->getKey();

        defer(static function () use ($id) {
            try {
                $payment = Payment::query()
                    ->with(['order.customer', 'order.salesPerson.employee'])
                    ->find($id);

                if (!$payment || !$payment->order) {
                    return;
                }

                app(ChequePaymentLifecycleService::class)->onChequeReceived($payment);
                app(ChequePaymentNotifier::class)->notifyNewCheque($payment);
            } catch (\Throwable $e) {
                Log::error('PaymentObserver cheque notify failed: '.$e->getMessage(), [
                    'payment_id' => $id,
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }, 'cheque-notify-payment-'.$id);
    }

    public function deleted(Payment $payment): void
    {
        try {
            app(SalesAccountingService::class)->reversePayment($payment);
        } catch (\Throwable $e) {
            Log::error('PaymentObserver accounting reversal failed: '.$e->getMessage(), [
                'payment_id' => $payment->getKey(),
            ]);
        }
    }

    public function updated(Payment $payment): void
    {
        if (!$payment->wasChanged([
            'entry_type',
            'amount',
            'payment_date',
            'payment_method',
            'reference_number',
        ])) {
            return;
        }

        try {
            if ($payment->isCheque()) {
                Log::warning('Cheque financial fields changed after creation; lifecycle journals were left unchanged.', [
                    'payment_id' => $payment->getKey(),
                ]);
                return;
            }

            $accounting = app(SalesAccountingService::class);
            $accounting->reversePayment($payment);
            $fresh = Payment::query()->with('order')->find($payment->getKey());
            if ($fresh) {
                $accounting->synchronizePayment($fresh);
            }
        } catch (\Throwable $e) {
            Log::error('PaymentObserver accounting update failed: '.$e->getMessage(), [
                'payment_id' => $payment->getKey(),
            ]);
        }
    }
}
