<?php

namespace App\Services\Accounting;

use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class TaxManagementService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function createTaxCode(int $businessId, array $data): int
    {
        if (! Schema::hasTable('acct_tax_codes')) {
            throw new RuntimeException('Tax tables missing.');
        }

        return (int) DB::table('acct_tax_codes')->insertGetId([
            'business_id' => $businessId,
            'code' => $data['code'],
            'name' => $data['name'],
            'tax_type' => $data['tax_type'],
            'rate' => $data['rate'],
            'effective_from' => $data['effective_from'],
            'effective_to' => $data['effective_to'] ?? null,
            'inclusive' => (bool) ($data['inclusive'] ?? false),
            'input_account_id' => $data['input_account_id'] ?? AccountingService::accountId(AccountingService::GL_TAX_RECEIVABLE),
            'output_account_id' => $data['output_account_id'] ?? AccountingService::accountId(AccountingService::GL_TAX_PAYABLE),
            'payable_account_id' => $data['payable_account_id'] ?? AccountingService::accountId(AccountingService::GL_TAX_PAYABLE),
            'receivable_account_id' => $data['receivable_account_id'] ?? AccountingService::accountId(AccountingService::GL_TAX_RECEIVABLE),
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function taxSummary(int $taxPeriodId): array
    {
        $period = DB::table('acct_tax_periods')->where('id', $taxPeriodId)->first();
        if (! $period) {
            throw new RuntimeException('Tax period not found.');
        }

        $codes = DB::table('acct_tax_codes')
            ->where('business_id', $period->business_id)
            ->where('active', 1)
            ->get();

        $lines = [];
        foreach ($codes as $code) {
            $accountIds = array_filter([
                (int) $code->input_account_id,
                (int) $code->output_account_id,
                (int) $code->payable_account_id,
                (int) $code->receivable_account_id,
            ]);
            if ($accountIds === []) {
                continue;
            }

            $row = DB::table('journal_entries')
                ->whereIn('chart_of_account_id', $accountIds)
                ->where('reversed', 0)
                ->whereBetween('date', [$period->starts_on, $period->ends_on])
                ->selectRaw('COALESCE(SUM(debit),0) as debits, COALESCE(SUM(credit),0) as credits')
                ->first();

            $lines[] = [
                'tax_code' => $code->code,
                'name' => $code->name,
                'rate' => (float) $code->rate,
                'debits' => round((float) ($row->debits ?? 0), 2),
                'credits' => round((float) ($row->credits ?? 0), 2),
                'net_payable' => round((float) ($row->credits ?? 0) - (float) ($row->debits ?? 0), 2),
            ];
        }

        $adjustments = Schema::hasTable('acct_tax_adjustments')
            ? DB::table('acct_tax_adjustments')->where('tax_period_id', $taxPeriodId)->get()
            : collect();

        return [
            'currency' => 'LKR',
            'period' => $period,
            'lines' => $lines,
            'adjustments' => $adjustments,
        ];
    }

    public static function postAdjustment(
        int $businessId,
        int $taxPeriodId,
        int $taxCodeId,
        float $amount,
        string $direction,
        string $eventKey,
        ?string $notes = null,
        ?int $userId = null
    ): string {
        if (! in_array($direction, ['input', 'output'], true)) {
            throw new RuntimeException('Invalid tax adjustment direction.');
        }

        $code = DB::table('acct_tax_codes')->where('id', $taxCodeId)->first();
        if (! $code) {
            throw new RuntimeException('Tax code not found.');
        }

        $amount = AccountingService::normalizeMoney($amount);
        $period = DB::table('acct_tax_periods')->where('id', $taxPeriodId)->first();
        if (! $period) {
            throw new RuntimeException('Tax period not found.');
        }
        if ($period->status === 'closed') {
            throw new RuntimeException('Tax period is closed.');
        }

        $taxAccount = $direction === 'input'
            ? (int) ($code->input_account_id ?: AccountingService::accountId(AccountingService::GL_TAX_RECEIVABLE))
            : (int) ($code->output_account_id ?: AccountingService::accountId(AccountingService::GL_TAX_PAYABLE));
        $offsetAccount = AccountingService::accountId(AccountingService::GL_GENERAL_EXPENSE);
        if (! $taxAccount || ! $offsetAccount) {
            throw new RuntimeException('Tax GL accounts not configured.');
        }

        $debit = $direction === 'input' ? $taxAccount : $offsetAccount;
        $credit = $direction === 'input' ? $offsetAccount : $taxAccount;

        $txn = AccountingService::postOnce(
            $eventKey,
            'tax_adjustment',
            $taxPeriodId,
            $debit,
            $credit,
            $amount,
            (string) $period->ends_on,
            'Tax adjustment '.$code->code,
            'TAX-'.$taxPeriodId,
            'tax_adjustment',
        );

        if (Schema::hasTable('acct_tax_adjustments')) {
            DB::table('acct_tax_adjustments')->insert([
                'business_id' => $businessId,
                'tax_period_id' => $taxPeriodId,
                'tax_code_id' => $taxCodeId,
                'amount' => $amount,
                'direction' => $direction,
                'notes' => $notes,
                'journal_txn' => $txn,
                'created_by' => $userId,
                'event_key' => $eventKey,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $txn;
    }

    public static function closePeriod(int $taxPeriodId, ?int $userId = null): void
    {
        DB::table('acct_tax_periods')->where('id', $taxPeriodId)->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by' => $userId,
            'updated_at' => now(),
        ]);
    }
}