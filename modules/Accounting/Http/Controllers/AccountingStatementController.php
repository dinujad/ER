<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\Accounting\StatementPdfService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Services\FlashService;

class AccountingStatementController extends Controller
{
    public function customerForm(Request $request)
    {
        return view('accounting::phase2.ar.customer_statement', [
            'customerId' => (int) $request->get('customer_id'),
            'from' => $request->get('from') ?: date('Y-01-01'),
            'to' => $request->get('to') ?: date('Y-m-d'),
        ]);
    }

    public function customerPdf(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|integer',
            'from' => 'required|date',
            'to' => 'required|date',
        ]);

        try {
            $pdf = StatementPdfService::customerStatement(
                (int) $request->customer_id,
                $request->from,
                $request->to
            );

            return $pdf->download('customer-statement-'.$request->customer_id.'.pdf');
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }
    }

    public function supplierForm(Request $request)
    {
        return view('accounting::phase2.ap.supplier_statement', [
            'supplierId' => (int) $request->get('supplier_id'),
            'from' => $request->get('from') ?: date('Y-01-01'),
            'to' => $request->get('to') ?: date('Y-m-d'),
        ]);
    }

    public function supplierPdf(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|integer',
            'from' => 'required|date',
            'to' => 'required|date',
        ]);

        try {
            $pdf = StatementPdfService::supplierStatement(
                (int) $request->supplier_id,
                $request->from,
                $request->to
            );

            return $pdf->download('supplier-statement-'.$request->supplier_id.'.pdf');
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }
    }

    public function bankReconForm(Request $request)
    {
        return view('accounting::phase2.bank_matching.recon_report', [
            'accountId' => (int) $request->get('chart_of_account_id'),
            'from' => $request->get('from') ?: date('Y-m-01'),
            'to' => $request->get('to') ?: date('Y-m-d'),
            'ending' => $request->get('ending_balance'),
        ]);
    }

    public function bankReconPdf(Request $request)
    {
        $request->validate([
            'chart_of_account_id' => 'required|integer',
            'from' => 'required|date',
            'to' => 'required|date',
            'ending_balance' => 'nullable|numeric',
        ]);

        try {
            $pdf = StatementPdfService::bankReconciliationReport(
                (int) $request->chart_of_account_id,
                $request->from,
                $request->to,
                $request->filled('ending_balance') ? (float) $request->ending_balance : null
            );

            return $pdf->download('bank-reconciliation-'.$request->chart_of_account_id.'.pdf');
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }
    }
}
