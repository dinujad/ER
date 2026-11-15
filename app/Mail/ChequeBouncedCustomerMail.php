<?php

namespace App\Mail;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ChequeBouncedCustomerMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Payment $payment,
        public Order $order,
        public Customer $customer,
        public string $reason,
    ) {}

    public function build()
    {
        return $this->subject(config('app.name').' — Cheque returned ('.$this->order->order_number.')')
            ->view('emails.cheque_bounced_customer', [
                'payment' => $this->payment,
                'order' => $this->order,
                'customer' => $this->customer,
                'reason' => $this->reason,
            ]);
    }
}
