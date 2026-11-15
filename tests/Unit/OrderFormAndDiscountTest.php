<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductVariation;
use Tests\TestCase;

class OrderFormAndDiscountTest extends TestCase
{
    public function test_order_form_filename_uses_business_name_and_order_number(): void
    {
        $order = new Order(['order_number' => 'ORD107']);
        $order->setRelation('customer', new Customer([
            'business_name' => 'Kamal Textiles / "Shop"',
            'name' => 'Kamal',
        ]));

        $this->assertSame('KAMAL_TEXTILES_SHOP_ORD107.pdf', $order->orderFormFilename());
    }

    public function test_order_form_filename_uses_shop_name_and_order_number_with_underscores(): void
    {
        $order = new Order(['order_number' => 'ORD001']);
        $order->setRelation('customer', new Customer([
            'business_name' => 'ABC Tex',
            'name' => 'Kamal',
        ]));

        $this->assertSame('ABC_TEX_ORD001.pdf', $order->orderFormFilename());
        $this->assertSame('attachment; filename="ABC_TEX_ORD001.pdf"', $order->orderFormContentDisposition());
    }

    public function test_discount_breakdown_caps_percentage_and_fixed_amounts(): void
    {
        $percent = Order::discountBreakdown(1000, 'percentage', 150);
        $this->assertSame(1000.0, $percent['discount_amount']);
        $this->assertSame(0.0, $percent['total_amount']);

        $fixed = Order::discountBreakdown(800, 'fixed', 500);
        $this->assertSame(500.0, $fixed['discount_amount']);
        $this->assertSame(300.0, $fixed['total_amount']);

        $none = Order::discountBreakdown(800, null, 0);
        $this->assertSame(0.0, $none['discount_amount']);
        $this->assertSame(800.0, $none['total_amount']);
    }

    public function test_variation_belongs_to_product_when_ids_are_string_and_int(): void
    {
        $variation = new ProductVariation();
        $variation->product_id = '218';

        $this->assertTrue($variation->belongsToProduct(218));
        $this->assertFalse($variation->belongsToProduct('219'));
    }
}
