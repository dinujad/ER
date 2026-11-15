<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

class SalesCommissionController extends Controller
{
    public function index(Request $request)
    {
        $salesPersons = User::where('role', 'sales_person')->get();

        $selectedSalesPersonId = $request->input('sales_person_id');
        $selectedMonth = $request->input('month', Carbon::now()->format('Y-m'));

        $salesCommission = null;
        if ($selectedSalesPersonId) {
            $salesCommission = \App\Models\SalesCommission::where('user_id', $selectedSalesPersonId)
                ->where('month', $selectedMonth)
                ->first();
        }

        $data = $this->getCommissionData($selectedSalesPersonId, $selectedMonth, $salesCommission);

        return view('erp.commissions.index', array_merge(['salesPersons' => $salesPersons], $data));
    }

    public function exportPdf(Request $request)
    {
        $salesPersonId = $request->input('sales_person_id');
        $month = $request->input('month', Carbon::now()->format('Y-m'));

        if (!$salesPersonId) {
            return back()->with('error', 'Please select a sales person.');
        }

        $salesCommission = \App\Models\SalesCommission::where('user_id', $salesPersonId)
            ->where('month', $month)
            ->first();

        $data = $this->getCommissionData($salesPersonId, $month, $salesCommission);
        $data['salesPerson'] = User::find($salesPersonId);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('erp.commissions.pdf', $data);
        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('commission_report_' . $month . '.pdf');
    }

    private function getCommissionData($selectedSalesPersonId, $selectedMonth, $salesCommission = null)
    {
        $orders = collect();
        $totalCommission = 0;
        $totalCashCollection = 0;
        $totalChequeCollection = 0;
        $totalCashCommission = 0;
        $totalChequeCommission = 0;

        if ($selectedSalesPersonId) {
            $startDate = Carbon::parse($selectedMonth)->startOfMonth();
            $endDate = Carbon::parse($selectedMonth)->endOfMonth();

            $orders = Order::with(['customer', 'payments'])
                ->where('user_id', $selectedSalesPersonId)
                ->whereBetween('order_date', [$startDate, $endDate])
                ->get();

            foreach ($orders as $order) {
                $cashCollection = $order->payments->whereIn('payment_method', ['cash', 'bank_transfer'])->sum('amount');
                $chequeCollection = $order->payments->where('payment_method', 'cheque')->sum('amount');
                
                $order->cash_collection = $cashCollection;
                $order->cheque_collection = $chequeCollection;
                
                // Per-row commission (for table display if needed, currently used for record)
                $order->cash_commission = ($cashCollection * $order->commission_rate) / 100;
                $order->cheque_commission = ($chequeCollection * $order->commission_rate) / 100;
                $order->commission_amount = $order->cash_commission + $order->cheque_commission;

                $totalCashCollection += $cashCollection;
                $totalChequeCollection += $chequeCollection;
                
                $lastPayment = $order->payments->sortByDesc('payment_date')->first();
                $order->last_payment_date = $lastPayment ? $lastPayment->payment_date : null;
                $order->days_taken = $lastPayment ? $order->order_date->diffInDays($lastPayment->payment_date) : '-';
            }

            // Summary Calculation based on Global Rates (if set)
            $cashRate = $salesCommission ? $salesCommission->cash_rate : 0;
            $chequeRate = $salesCommission ? $salesCommission->cheque_rate : 0;

            $totalCashCommission = ($totalCashCollection * $cashRate) / 100;
            $totalChequeCommission = ($totalChequeCollection * $chequeRate) / 100;
            $totalCommission = $totalCashCommission + $totalChequeCommission;
        }

        return compact(
            'orders', 
            'selectedSalesPersonId', 
            'selectedMonth', 
            'totalCommission', 
            'totalCashCollection', 
            'totalChequeCollection',
            'totalCashCommission',
            'totalChequeCommission',
            'salesCommission'
        );
    }

    public function update(Request $request)
    {
        $request->validate([
            'sales_person_id' => 'required|exists:users,id',
            'month' => 'required|date_format:Y-m',
            'rates' => 'nullable|array',
            'rates.*' => 'nullable|numeric|min:0',
            'cash_rate' => 'nullable|numeric|min:0',
            'cheque_rate' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:pending,paid',
            'payment_method' => 'nullable|in:cash,bank_transfer',
        ]);

        // Update Monthly Global Rates & Payment Status
        $commission = \App\Models\SalesCommission::firstOrNew([
            'user_id' => $request->sales_person_id,
            'month' => $request->month
        ]);

        $commission->cash_rate = $request->input('cash_rate') ?? 0;
        $commission->cheque_rate = $request->input('cheque_rate') ?? 0;
        
        $oldStatus = $commission->status;
        $commission->status = $request->input('status', 'pending');
        $commission->payment_method = $commission->status === 'paid' ? $request->input('payment_method') : null;

        if ($commission->status === 'paid' && $oldStatus !== 'paid') {
            $commission->paid_at = now();
        } elseif ($commission->status === 'pending') {
            $commission->paid_at = null;
        }

        $commission->save();

        // Update Per-Order Rates (if present)
        if ($request->has('rates')) {
            foreach ($request->rates as $orderId => $rate) {
                $order = Order::find($orderId);
                if ($order) {
                    $order->update(['commission_rate' => $rate]);
                }
            }
        }

        return back()->with('success', 'Commission rates updated successfully.');
    }
    public function history(Request $request)
    {
        $query = \App\Models\SalesCommission::with('user');

        if ($request->filled('sales_person_id')) {
            $query->where('user_id', $request->sales_person_id);
        }

        $commissions = $query->orderBy('month', 'desc')->get();
        $salesPersons = User::where('role', 'sales_person')->get();

        // Calculate totals for each record
        foreach ($commissions as $commission) {
            // Reuse the calculation logic
            $startDate = Carbon::parse($commission->month)->startOfMonth();
            $endDate = Carbon::parse($commission->month)->endOfMonth();

            $orders = Order::with(['payments'])
                ->where('user_id', $commission->user_id)
                ->whereBetween('order_date', [$startDate, $endDate])
                ->get();

            $totalCashCollection = 0;
            $totalChequeCollection = 0;

            foreach ($orders as $order) {
                $totalCashCollection += $order->payments->whereIn('payment_method', ['cash', 'bank_transfer'])->sum('amount');
                $totalChequeCollection += $order->payments->where('payment_method', 'cheque')->sum('amount');
            }

            $totalCashCommission = ($totalCashCollection * $commission->cash_rate) / 100;
            $totalChequeCommission = ($totalChequeCollection * $commission->cheque_rate) / 100;
            
            $commission->total_amount = $totalCashCommission + $totalChequeCommission;
        }

        return view('erp.commissions.history', compact('commissions', 'salesPersons'));
    }
}
