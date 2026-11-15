<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionPaymentDeleted
{
    use Dispatchable, SerializesModels;

    public mixed $payment;

    public function __construct(mixed $payment = null)
    {
        $this->payment = $payment;
    }
}
