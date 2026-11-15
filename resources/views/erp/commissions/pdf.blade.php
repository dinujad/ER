<!DOCTYPE html>
<html>
<head>
    <title>Sales Commission Report</title>
    <style>
        @page { margin: 20px; }
        body { font-family: sans-serif; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; text-transform: uppercase; font-weight: bold; font-size: 14px; }
        .sub-header { margin-bottom: 10px; font-weight: bold; text-transform: uppercase; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; table-layout: fixed; }
        th, td { border: 1px solid #000; padding: 4px; vertical-align: middle; word-wrap: break-word; }
        th { background-color: #f2f2f2; text-align: center; text-transform: uppercase; font-size: 9px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer-row { background-color: #d1d1d1; font-weight: bold; text-transform: uppercase; }
        .calculation-section { margin-top: 20px; font-size: 11px; }
        .calc-row { margin-bottom: 5px; }
        .calc-label { display: inline-block; width: 200px; }
        .calc-amount { display: inline-block; width: 100px; text-align: right; margin-right: 50px; }
        .calc-result { display: inline-block; width: 100px; text-align: right; font-weight: bold; }
        .double-underline { border-bottom: 3px double black; display: inline-block; }
        .total-label { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        {{ \Carbon\Carbon::parse($selectedMonth)->format('Y') }} MONTH OF {{ strtoupper(\Carbon\Carbon::parse($selectedMonth)->format('F')) }} CASH/CHEQUE RECEIVING & CREDIT
    </div>

    <div class="sub-header">
        SALES MANAGER -: {{ strtoupper($salesPerson->name) }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">NO</th>
                <th>INVOICE DATE</th>
                <th>INVOICE NUMBER</th>
                <th>CUSTOMER</th>
                <th>INVOICE AMOUNT</th>
                <th>CASH/ACCOUNT TRANSFER</th>
                <th>CHEQUE</th>
                <th>CREDIT</th>
                <th>CASH/CHEQUE RECEIVE DATE</th>
                <th>DAYS</th>
                <th>RATE</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $index => $order)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center">{{ $order->order_date->format('d.m.Y') }}</td>
                <td class="text-center">{{ $order->order_number }}</td>
                <td>{{ optional($order->customer)->name }}</td>
                <td class="text-right">{{ number_format($order->total_amount, 2) }}</td>
                <td class="text-right">{{ $order->cash_collection > 0 ? number_format($order->cash_collection, 2) : '' }}</td>
                <td class="text-right">{{ $order->cheque_collection > 0 ? number_format($order->cheque_collection, 2) : '' }}</td>
                <td class="text-right">{{ $order->due_amount > 0 ? number_format($order->due_amount, 2) : '' }}</td>
                <td class="text-center">{{ $order->last_payment_date ? $order->last_payment_date->format('d.m.Y') : '' }}</td>
                <td class="text-center">{{ is_numeric($order->days_taken) ? number_format($order->days_taken, 0) : $order->days_taken }}</td>
                <td class="text-center">{{ $order->commission_rate }}%</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="footer-row">
                <td colspan="4">TOTAL SALE / RETURN MONTH OF {{ strtoupper(\Carbon\Carbon::parse($selectedMonth)->format('F')) }}</td>
                <td class="text-right">{{ number_format($orders->sum('total_amount'), 2) }}</td>
                <td class="text-right">{{ number_format($totalCashCollection, 2) }}</td>
                <td class="text-right">{{ number_format($totalChequeCollection, 2) }}</td>
                <td class="text-right">{{ number_format($orders->sum('due_amount'), 2) }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>

    <div class="calculation-section">
        <div style="text-decoration: underline; margin-bottom: 10px; font-weight: bold;">COMMISSION CALCULATE -:</div>
        
        <div class="calc-row">
            <span class="calc-label">1 TOTAL CASH COLLECTION</span>
            <span class="calc-amount" style="width: 180px; margin-right: 0;">{{ number_format($totalCashCollection, 2) }} * {{ $salesCommission->cash_rate ?? 0 }}%</span>
            <span class="calc-result">{{ number_format($totalCashCommission, 2) }}</span>
        </div>
        
        <div class="calc-row">
            <span class="calc-label">2 TOTAL CHEQUE COLLECTION</span>
            <span class="calc-amount" style="width: 180px; margin-right: 0;">{{ number_format($totalChequeCollection, 2) }} * {{ $salesCommission->cheque_rate ?? 0 }}%</span>
            <span class="calc-result">{{ number_format($totalChequeCommission, 2) }}</span>
        </div>
        
        <div class="calc-row" style="margin-top: 10px;">
            <span class="calc-label total-label">TOTAL</span>
            <span class="calc-amount"></span>
            <span class="calc-result double-underline">{{ number_format($totalCommission, 2) }}</span>
        </div>
    </div>

    <div style="margin-top: 30px; font-size: 11px; font-weight: bold;">
        <div style="margin-bottom: 5px;">STATUS: {{ strtoupper($salesCommission->status ?? 'PENDING') }}</div>
        @if(($salesCommission->status ?? '') === 'paid')
            <div style="margin-bottom: 5px;">PAYMENT METHOD: {{ strtoupper(str_replace('_', ' ', $salesCommission->payment_method)) }}</div>
            @if($salesCommission->payment_method === 'bank_transfer' && $salesPerson->bank_account_number)
                <div style="margin-bottom: 5px;">BANK DETAILS: {{ $salesPerson->bank_account_number }}</div>
            @endif
            @if($salesCommission->paid_at)
                <div style="margin-bottom: 5px;">PAID DATE: {{ \Carbon\Carbon::parse($salesCommission->paid_at)->format('d.m.Y') }}</div>
            @endif
        @endif
    </div>
</body>
</html>
