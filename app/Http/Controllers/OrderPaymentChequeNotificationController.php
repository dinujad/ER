<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\ChequePaymentNotifier;
use Illuminate\Http\Request;

class OrderPaymentChequeNotificationController extends Controller
{
    public function send(Request $request, Payment $payment)
    {
        $request->validate([
            'message' => 'required|string|min:5|max:2000',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:whatsapp,sms,email',
        ]);

        if (strtolower((string) $payment->payment_method) !== 'cheque') {
            return back()->withErrors(['message' => 'This payment is not a cheque payment.']);
        }

        $payment->loadMissing(['order.customer', 'order.salesPerson.employee']);
        if (!$payment->order || !$payment->order->customer) {
            return back()->withErrors(['message' => 'No customer linked to this payment.']);
        }

        $results = app(ChequePaymentNotifier::class)->notifyCustomerReminder(
            $payment,
            $request->input('message'),
            $request->input('channels'),
        );

        $sent   = array_filter($results, fn ($v) => $v === true);
        $failed = array_filter($results, fn ($v) => $v === false);

        $msg = 'Reminder sent via: '.implode(', ', array_keys($sent));
        if ($failed) {
            $msg .= '. Failed: '.implode(', ', array_keys($failed)).'.';
        }
        if (!$sent) {
            return back()->with('reminder_error', 'All channels failed. Check that the customer has a phone / email and that service credentials are configured.');
        }

        return back()->with('reminder_success', $msg);
    }
}
