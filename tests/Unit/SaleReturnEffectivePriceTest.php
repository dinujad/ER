<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SaleReturnEffectivePriceTest extends TestCase
{
    public function test_unpaid_open_bill_uses_invoice_adjusted_list_price(): void
    {
        $order = new Order([
            'subtotal' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'claim_credit_amount' => 0,
            'status' => Order::STATUS_AFTERCHECKING,
        ]);
        $order->setRelation('payments', new Collection());
        $order->setRelation('saleReturns', new Collection());

        $item = new OrderItem(['unit_price' => 1000, 'quantity' => 1, 'quantity_returned' => 0]);
        $item->setRelation('order', $order);

        $this->assertSame(1000.0, $item->effectiveReturnUnitPrice());
    }

    public function test_invoice_percentage_discount_reduces_effective_unit(): void
    {
        $order = new Order([
            'subtotal' => 1000,
            'discount_amount' => 100,
            'total_amount' => 900,
            'claim_credit_amount' => 0,
            'status' => Order::STATUS_COMPLETED,
        ]);
        $order->setRelation('payments', new Collection());
        $order->setRelation('saleReturns', new Collection());

        $item = new OrderItem(['unit_price' => 1000, 'quantity' => 1, 'quantity_returned' => 0]);
        $item->setRelation('order', $order);

        $this->assertSame(900.0, $item->effectiveReturnUnitPrice());
    }

    public function test_cash_next_payment_discount_drives_effective_unit_to_850(): void
    {
        $order = new Order([
            'subtotal' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'claim_credit_amount' => 0,
            'status' => Order::STATUS_COMPLETED,
        ]);

        $payment = new Payment([
            'entry_type' => Payment::ENTRY_PAYMENT,
            'payment_method' => 'cash',
            'amount' => 850,
            'discount_amount' => 150,
            'settlement_amount' => 1000,
        ]);

        $order->setRelation('payments', new Collection([$payment]));
        $order->setRelation('saleReturns', new Collection());

        $item = new OrderItem(['unit_price' => 1000, 'quantity' => 1, 'quantity_returned' => 0]);
        $item->setRelation('order', $order);

        $this->assertSame(850.0, $item->effectiveReturnUnitPrice());
    }
}
