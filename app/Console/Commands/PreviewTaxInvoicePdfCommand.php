<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class PreviewTaxInvoicePdfCommand extends Command
{
    protected $signature = 'dermas:preview-tax-invoice {--out=}';

    protected $description = 'Render a sample TAX INVOICE PDF (gazette layout) for preview';

    public function handle(): int
    {
        $order = $this->mockOrder();
        $pdf = Pdf::loadView('erp.sales_persons.orders.invoice', compact('order'))
            ->setPaper('a4', 'portrait');

        $out = $this->option('out')
            ?: storage_path('app/tax-invoice-preview-' . $order->taxInvoiceSerialNumber() . '.pdf');

        file_put_contents($out, $pdf->output());
        $this->info('Wrote: ' . $out);

        return self::SUCCESS;
    }

    protected function mockOrder(): Order
    {
        $customer = new Customer([
            'name' => 'Nimal Perera',
            'business_name' => 'Bandarawela Fashion House',
            'business_address' => 'Main Street, Bandarawela',
            'phone' => '0771234567',
            'tin' => '123456789',
        ]);

        $salesPerson = new User(['name' => 'Kasun Silva', 'phone' => '0771039230']);

        $productA = new Product(['name' => 'Men Formal Trouser', 'sku' => 'DFT-001']);
        $productB = new Product(['name' => 'Kids Short', 'sku' => 'KDS-014']);

        $item1 = new OrderItem([
            'quantity' => 10,
            'quantity_returned' => 0,
            'unit_price' => 1850,
            'total_price' => 18500,
        ]);
        $item1->setRelation('product', $productA);
        $item1->setRelation('variation', null);

        $item2 = new OrderItem([
            'quantity' => 5,
            'quantity_returned' => 0,
            'unit_price' => 950,
            'total_price' => 4750,
        ]);
        $item2->setRelation('product', $productB);
        $item2->setRelation('variation', null);

        $payment = new Payment([
            'entry_type' => Payment::ENTRY_PAYMENT,
            'payment_method' => 'cash',
            'amount' => 20000,
            'discount_amount' => 0,
            'settlement_amount' => 20000,
        ]);

        $now = Carbon::parse('2026-07-15 10:00:00');
        $order = new Order([
            'order_number' => 'ORD1001',
            'invoice_number' => '26JUL-DERM-1001',
            'status' => Order::STATUS_COMPLETED,
            'subtotal' => 23250,
            'discount_amount' => 0,
            'shipping' => 0,
            'invoice_other_expenses' => 0,
            'total_amount' => 23250,
            'issued_bags_count' => 2,
            'issued_bundle_count' => 1,
            'order_date' => $now->toDateString(),
        ]);
        $order->id = 1001;
        $order->created_at = $now;
        $order->updated_at = $now;
        $order->setRelation('customer', $customer);
        $order->setRelation('salesPerson', $salesPerson);
        $order->setRelation('items', new Collection([$item1, $item2]));
        $order->setRelation('payments', new Collection([$payment]));
        $order->setRelation('saleReturns', new Collection());

        return $order;
    }
}
