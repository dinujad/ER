<?php

namespace Tests\Unit;

use App\Http\Controllers\PurchaseController;
use App\Models\Purchase;
use App\Services\MaterialStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use ReflectionClass;
use Tests\TestCase;

class PurchasePaymentValidationTest extends TestCase
{
    private function makeController(): PurchaseController
    {
        return new PurchaseController($this->createMock(MaterialStockService::class));
    }

    private function invokeValidate(Collection $payments, float $total, string $type): ?string
    {
        $controller = $this->makeController();
        $method = (new ReflectionClass($controller))->getMethod('validatePaymentTotals');
        $method->setAccessible(true);

        return $method->invoke($controller, $payments, $total, $type);
    }

    private function invokeNormalize(array $row): array
    {
        $controller = $this->makeController();
        $method = (new ReflectionClass($controller))->getMethod('normalizePaymentRow');
        $method->setAccessible(true);

        return $method->invoke($controller, $row);
    }

    private function invokeCollect(Request $request): Collection
    {
        $controller = $this->makeController();
        $method = (new ReflectionClass($controller))->getMethod('collectPayments');
        $method->setAccessible(true);

        return $method->invoke($controller, $request);
    }

    public function test_cheque_payment_requires_cheque_fields(): void
    {
        $payments = collect([
            [
                'amount' => 50,
                'payment_method' => 'cheque',
                'cheque_number' => '',
                'cheque_date' => '',
                'cheque_bank' => '',
            ],
        ]);

        $this->assertSame(
            'Cheque number is required for cheque payments.',
            $this->invokeValidate($payments, 100, Purchase::TYPE_CREDIT)
        );
    }

    public function test_full_payment_type_requires_exact_total(): void
    {
        $payments = collect([
            ['amount' => 50, 'payment_method' => 'cash'],
        ]);

        $this->assertSame(
            'Full payment purchases: payment total must equal grand total.',
            $this->invokeValidate($payments, 100, Purchase::TYPE_CASH)
        );
    }

    public function test_payments_cannot_exceed_total(): void
    {
        $payments = collect([
            ['amount' => 150, 'payment_method' => 'cash'],
        ]);

        $this->assertSame(
            'Total payments cannot exceed grand total.',
            $this->invokeValidate($payments, 100, Purchase::TYPE_CREDIT)
        );
    }

    public function test_normalize_cheque_clears_bank_reference(): void
    {
        $row = $this->invokeNormalize([
            'amount' => 100,
            'payment_method' => 'cheque',
            'reference' => 'should-clear',
            'cheque_number' => 'CHQ-1',
            'cheque_date' => '2026-05-20',
            'cheque_bank' => 'BOC',
        ]);

        $this->assertNull($row['reference']);
        $this->assertSame('CHQ-1', $row['cheque_number']);
        $this->assertSame('BOC', $row['cheque_bank']);
    }

    public function test_collect_payments_filters_zero_amount_rows(): void
    {
        $request = Request::create('/', 'POST', [
            'payments' => [
                ['amount' => 0, 'payment_method' => 'cash'],
                ['amount' => 75, 'payment_method' => 'cash'],
            ],
        ]);

        $collected = $this->invokeCollect($request);

        $this->assertCount(1, $collected);
        $this->assertEquals(75.0, $collected->first()['amount']);
    }
}
