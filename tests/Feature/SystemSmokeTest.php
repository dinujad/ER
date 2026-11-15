<?php

namespace Tests\Feature;

use App\Models\ManufacturingJob;
use App\Models\Order;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SystemSmokeTest extends TestCase
{
    public function test_application_uses_sri_lanka_timezone(): void
    {
        $this->assertSame('Asia/Colombo', config('app.timezone'));
        $this->assertSame('Asia/Colombo', date_default_timezone_get());
        $this->assertSame('Asia/Colombo', now()->timezoneName);
    }

    public function test_home_redirects_unauthenticated(): void
    {
        $response = $this->get('/');
        $this->assertContains($response->status(), [200, 302]);
    }

    public function test_sales_person_dashboard_requires_auth(): void
    {
        $this->get('/sales-person/dashboard')->assertRedirect();
    }

    public function test_driver_dashboard_requires_auth(): void
    {
        $this->get('/driver/dashboard')->assertRedirect();
    }

    public function test_erp_login_page_loads(): void
    {
        $response = $this->get('/login');
        $this->assertContains($response->status(), [200, 302]);
    }

    public function test_order_payment_gate_after_checking_only(): void
    {
        $order = new Order(['status' => 'pending']);
        $this->assertFalse($order->canAcceptPayments());

        $order->status = Order::STATUS_AFTERCHECKING;
        $this->assertTrue($order->canAcceptPayments());

        $order->status = Order::STATUS_COMPLETED;
        $this->assertTrue($order->canAcceptPayments());
    }

    public function test_barcode_setting_fallback_never_divides_by_zero(): void
    {
        $setting = new \App\Models\BarcodeSetting(['stickers_per_row' => 0]);
        $this->assertSame(1, $setting->stickersPerRow());
        $this->assertSame(1.50, \App\Models\BarcodeSetting::defaultGarmentRoll()['sticker_width']);
    }

    public function test_variation_barcode_prefers_own_code_then_sku(): void
    {
        $variation = new \App\Models\ProductVariation([
            'barcode' => '',
            'sub_sku' => 'TSHIRT-M',
        ]);
        $this->assertSame('TSHIRT-M', $variation->barcodeValue());

        $variation->barcode = '1234567890128';
        $this->assertSame('1234567890128', $variation->barcodeValue());

        $variation->combo_variations = ['Color' => 'Red', 'Size' => 'M'];
        $this->assertSame('Red', $variation->attributeValue('Colour', 'Color'));
        $this->assertSame('M', $variation->attributeValue('Size'));
        $this->assertStringContainsString('red', $variation->searchHaystack());
    }

    public function test_pos_sale_is_retail_and_returnable_when_completed(): void
    {
        $order = new Order(['status' => Order::STATUS_COMPLETED, 'register_id' => 1]);
        $this->assertTrue($order->isRetailSale());
        $this->assertTrue($order->canAcceptSaleReturn());
        $this->assertSame('retail', $order->stockChannel());
    }

    public function test_afterchecking_orders_can_accept_sale_return(): void
    {
        $order = new Order(['status' => Order::STATUS_AFTERCHECKING]);
        $this->assertTrue($order->canAcceptSaleReturn());
    }

    public function test_pos_quotation_is_retail_quotation_not_a_sale(): void
    {
        $order = new Order(['status' => Order::STATUS_QUOTATION, 'register_id' => 1]);
        $this->assertTrue($order->isRetailQuotation());
        $this->assertFalse($order->canAcceptSaleReturn());
        $this->assertContains(Order::STATUS_QUOTATION, Order::excludedFromSalesStats());
    }

    public function test_manufacturing_legacy_completed_progress(): void
    {
        $job = new ManufacturingJob(['status' => 'completed', 'total_cut_pieces' => 10]);
        $percent = $job->progressPercent();
        $this->assertGreaterThanOrEqual(0, $percent);
        $this->assertLessThanOrEqual(100, $percent);
    }

    public function test_sales_person_expense_types_defined(): void
    {
        $this->assertNotEmpty(\App\Models\SalesPersonExpense::typeOptions());
    }

    public function test_erp_routes_point_to_existing_controller_methods(): void
    {
        $missing = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getAction();
            if (! isset($action['controller']) || ! is_string($action['controller'])) {
                continue;
            }

            if (! str_contains($action['controller'], '@')) {
                continue;
            }

            [$class, $method] = explode('@', $action['controller'], 2);

            if (! str_starts_with($route->uri(), 'erp/')) {
                continue;
            }

            if (! class_exists($class) || ! method_exists($class, $method)) {
                $missing[] = ($route->getName() ?? $route->uri()) . " => {$class}@{$method}";
            }
        }

        $this->assertEmpty(
            $missing,
            "ERP routes reference missing controller methods:\n" . implode("\n", $missing)
        );
    }
}
