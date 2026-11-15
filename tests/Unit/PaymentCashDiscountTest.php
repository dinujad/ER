<?php

namespace Tests\Unit;

use App\Models\Payment;
use Tests\TestCase;

class PaymentCashDiscountTest extends TestCase
{
    public function test_cash_percentage_is_taken_off_the_next_payment(): void
    {
        $totals = Payment::computeLineTotals(50000, 'percentage', 5, true);

        $this->assertSame(50000.0, $totals['amount']);
        $this->assertSame(2500.0, $totals['discount_amount']);
        $this->assertSame(52500.0, $totals['settlement_amount']);
    }

    public function test_other_methods_still_reduce_the_amount_received(): void
    {
        $totals = Payment::computeLineTotals(50000, 'percentage', 5, false);

        $this->assertSame(47500.0, $totals['amount']);
        $this->assertSame(2500.0, $totals['discount_amount']);
        $this->assertSame(50000.0, $totals['settlement_amount']);
    }
}
