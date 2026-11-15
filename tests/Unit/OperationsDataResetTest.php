<?php

namespace Tests\Unit;

use App\Services\OperationsDataReset;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OperationsDataResetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'purchase_return_items',
            'purchase_returns',
            'purchase_payments',
            'purchase_items',
            'purchases',
            'sales_person_expenses',
            'expenses',
            'material_stock_movements',
            'stock_ledgers',
            'materials',
            'orders',
            'journal_entries',
            'accounting_postings',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_no')->nullable();
        });
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id');
        });
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id')->nullable();
        });
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id');
        });
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_return_id');
        });
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 12, 2)->default(0);
        });
        Schema::create('sales_person_expenses', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 12, 2)->default(0);
        });
        Schema::create('material_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_id');
        });
        Schema::create('stock_ledgers', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_type');
        });
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->decimal('quantity', 12, 2)->default(0);
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->nullable();
        });
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number');
            $table->string('transaction_type')->nullable();
        });
        Schema::create('accounting_postings', function (Blueprint $table) {
            $table->id();
            $table->string('event_key');
            $table->string('transaction_number');
            $table->string('event_type');
        });
    }

    public function test_resets_purchases_inventory_and_expenses_only(): void
    {
        DB::table('purchases')->insert(['purchase_no' => 'PO-1']);
        DB::table('purchase_items')->insert(['purchase_id' => 1]);
        DB::table('purchase_payments')->insert(['purchase_id' => 1]);
        DB::table('purchase_returns')->insert(['purchase_id' => 1]);
        DB::table('purchase_return_items')->insert(['purchase_return_id' => 1]);
        DB::table('expenses')->insert(['amount' => 100]);
        DB::table('sales_person_expenses')->insert(['amount' => 50]);
        DB::table('material_stock_movements')->insert(['material_id' => 1]);
        DB::table('stock_ledgers')->insert(['transaction_type' => 'purchase']);
        DB::table('materials')->insert(['quantity' => 25]);
        DB::table('orders')->insert(['order_number' => 'ORD-KEEP']);
        DB::table('journal_entries')->insert([
            ['transaction_number' => 'TXN-EXP', 'transaction_type' => 'expense'],
            ['transaction_number' => 'TXN-SALE', 'transaction_type' => 'sale'],
        ]);
        DB::table('accounting_postings')->insert([
            ['event_key' => 'expense:1:entry', 'transaction_number' => 'TXN-EXP', 'event_type' => 'expense'],
            ['event_key' => 'sale:1:entry', 'transaction_number' => 'TXN-SALE', 'event_type' => 'sale'],
        ]);

        $result = (new OperationsDataReset())->reset();

        $this->assertSame(0, DB::table('purchases')->count());
        $this->assertSame(0, DB::table('purchase_items')->count());
        $this->assertSame(0, DB::table('purchase_payments')->count());
        $this->assertSame(0, DB::table('purchase_returns')->count());
        $this->assertSame(0, DB::table('purchase_return_items')->count());
        $this->assertSame(0, DB::table('expenses')->count());
        $this->assertSame(0, DB::table('sales_person_expenses')->count());
        $this->assertSame(0, DB::table('material_stock_movements')->count());
        $this->assertSame(0, DB::table('stock_ledgers')->count());
        $this->assertSame(0.0, (float) DB::table('materials')->value('quantity'));
        $this->assertSame(1, DB::table('orders')->count());
        $this->assertSame(0, DB::table('journal_entries')->where('transaction_type', 'expense')->count());
        $this->assertSame(1, DB::table('journal_entries')->where('transaction_type', 'sale')->count());
        $this->assertSame(0, DB::table('accounting_postings')->where('event_type', 'expense')->count());
        $this->assertSame(1, DB::table('accounting_postings')->where('event_type', 'sale')->count());
        $this->assertSame(1, $result['materials_reset']);
        $this->assertContains('purchases', $result['truncated']);
    }
}
