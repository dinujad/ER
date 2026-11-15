<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\ChequePaymentLifecycleService;
use Illuminate\Http\Request;

class OrderChequePaymentController extends Controller
{
    public function __construct(
        protected ChequePaymentLifecycleService $lifecycle,
    ) {}

    public function deposit(Payment $payment)
    {
        try {
            $this->lifecycle->markDeposited($payment);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Cheque marked as deposited to bank.');
    }

    public function clear(Payment $payment)
    {
        try {
            $this->lifecycle->markCleared($payment);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Cheque marked as cleared. Funds moved from PDC to Bank account.');
    }

    public function bounce(Request $request, Payment $payment)
    {
        $request->validate([
            'bounce_reason' => 'required|string|min:5|max:2000',
        ]);

        try {
            $this->lifecycle->markBounced($payment, $request->bounce_reason);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return back()->with(
            'success',
            'Cheque marked as returned/bounced. Order due amount restored. Customer and sales person notified.'
        );
    }
}
