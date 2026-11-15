<?php

namespace Tests\Unit;

use App\Support\SalesPersonSafeRedirect;
use Illuminate\Http\Request;
use Tests\TestCase;

class SalesPersonSafeRedirectTest extends TestCase
{
    public function test_accepts_relative_sales_person_path_with_fragment(): void
    {
        $this->assertSame(
            '/sales-person/route#expenses',
            SalesPersonSafeRedirect::path('/sales-person/route#expenses')
        );
    }

    public function test_keeps_month_query_string(): void
    {
        $this->assertSame(
            '/sales-person/expenses?month=2026-03#expenses',
            SalesPersonSafeRedirect::path('/sales-person/expenses?month=2026-03#expenses')
        );
    }

    public function test_accepts_absolute_url_matching_request_host(): void
    {
        $request = Request::create('https://erp.dermasapparel.com/sales-person/expenses', 'POST');
        config(['app.url' => 'https://erp.dermasapparel.com']);

        $this->assertSame(
            '/sales-person/route#expenses',
            SalesPersonSafeRedirect::path('https://erp.dermasapparel.com/sales-person/route#expenses', $request)
        );
    }

    public function test_rejects_external_host(): void
    {
        $request = Request::create('https://erp.dermasapparel.com/sales-person/expenses', 'POST');
        config(['app.url' => 'https://erp.dermasapparel.com']);

        $this->assertNull(
            SalesPersonSafeRedirect::path('https://evil.example/sales-person/route', $request)
        );
    }

    public function test_rejects_protocol_relative_url(): void
    {
        $this->assertNull(SalesPersonSafeRedirect::path('//evil.example/sales-person/route'));
    }
}
