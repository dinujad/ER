<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_postings', function (Blueprint $table) {
            $table->id();
            $table->string('event_key', 191)->unique();
            $table->string('transaction_number', 50)->index();
            $table->nullableMorphs('source');
            $table->string('event_type', 80)->index();
            $table->decimal('amount', 20, 4);
            $table->boolean('reversed')->default(false);
            $table->timestamps();
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('journal_entries', 'source_type')) {
                $table->string('source_type')->nullable()->after('reference')->index();
            }
            if (!Schema::hasColumn('journal_entries', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type')->index();
            }
            if (!Schema::hasColumn('journal_entries', 'payment_id')) {
                $table->unsignedBigInteger('payment_id')->nullable()->after('source_id')->index();
            }
            if (!Schema::hasColumn('journal_entries', 'order_id')) {
                $table->unsignedBigInteger('order_id')->nullable()->after('payment_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_postings');

        Schema::table('journal_entries', function (Blueprint $table) {
            $columns = array_filter(
                ['source_type', 'source_id', 'payment_id', 'order_id'],
                fn (string $column) => Schema::hasColumn('journal_entries', $column)
            );
            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
