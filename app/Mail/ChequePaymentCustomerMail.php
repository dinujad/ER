<?php

namespace App\Mail;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ChequePaymentCustomerMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Payment $payment,
        public Order $order,
        public Customer $customer,
    ) {}

    public function build()
    {
        return $this->subject(config('app.name').' — Cheque payment recorded ('.$this->order->order_number.')')
            ->view('emails.cheque_payment_customer', [
                'payment' => $this->payment,
                'order' => $this->order,
                'customer' => $this->customer,
            ]);
    }
}
