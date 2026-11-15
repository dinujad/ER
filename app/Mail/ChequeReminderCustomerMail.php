<?php

namespace App\Mail;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ChequeReminderCustomerMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Payment $payment,
        public Order $order,
        public Customer $customer,
        public string $messageBody,
    ) {}

    public function build()
    {
        return $this->subject(config('app.name').' — Cheque payment reminder ('.$this->order->order_number.')')
            ->view('emails.cheque_reminder_customer', [
                'payment' => $this->payment,
                'order' => $this->order,
                'customer' => $this->customer,
                'messageBody' => $this->messageBody,
            ]);
    }
}
