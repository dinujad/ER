@extends('erp.layout')

@section('title', 'Sales Commissions')

@section('content')
<div class="container-fluid">
    <h3 class="mb-4">Sales Commission Management</h3>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('commissions.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Sales Person</label>
                    <select name="sales_person_id" class="form-select" required>
                        <option value="">-- Select Sales Person --</option>
                        @foreach($salesPersons as $sp)
                            <option value="{{ $sp->id }}" {{ $selectedSalesPersonId == $sp->id ? 'selected' : '' }}>
                                {{ $sp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Month</label>
                    <input type="month" name="month" class="form-control" value="{{ $selectedMonth }}" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    @if($selectedSalesPersonId && $orders->isNotEmpty())
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    {{ \Carbon\Carbon::parse($selectedMonth)->format('Y') }} MONTH OF {{ strtoupper(\Carbon\Carbon::parse($selectedMonth)->format('F')) }} CASH/CHEQUE RECEIVING & CREDIT
                </h5>
                <div>
                    <a href="{{ route('commissions.export', ['sales_person_id' => $selectedSalesPersonId, 'month' => $selectedMonth]) }}" class="btn btn-secondary me-2" target="_blank">Export PDF</a>
                    <button type="submit" form="commission-form" class="btn btn-success">Save Rates</button>
                </div>
            </div>
            
            <div class="p-3">
                <strong>SALES MANAGER -: {{ strtoupper($salesPersons->find($selectedSalesPersonId)->name) }}</strong>
            </div>

            <div class="card-body p-0">
                @canRoute('commissions.update')
<form action="{{ route('commissions.update') }}" method="POST" id="commission-form">
                    @csrf
                    <input type="hidden" name="sales_person_id" value="{{ $selectedSalesPersonId }}">
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover mb-0" style="font-size: 0.9rem;">
                            <thead class="table-secondary text-center align-middle text-uppercase">
                                <tr>
                                    <th>NO</th>
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
                                    <td class="text-end">{{ number_format($order->total_amount, 2) }}</td>
                                    
                                    {{-- Cash/Transfer --}}
                                    <td class="text-end">{{ $order->cash_collection > 0 ? number_format($order->cash_collection, 2) : '' }}</td>
                                    
                                    {{-- Cheque --}}
                                    <td class="text-end">{{ $order->cheque_collection > 0 ? number_format($order->cheque_collection, 2) : '' }}</td>
                                    
                                    {{-- Credit (Due) --}}
                                    <td class="text-end">{{ $order->due_amount > 0 ? number_format($order->due_amount, 2) : '' }}</td>
                                    
                                    {{-- Payment Date --}}
                                    <td class="text-center">
                                        {{ $order->last_payment_date ? $order->last_payment_date->format('d.m.Y') : '' }}
                                    </td>
                                    <td class="text-center">{{ $order->days_taken !== '-' ? number_format($order->days_taken, 0) : '-' }}</td>
                                    
                                    {{-- Rate Input --}}
                                    <td style="width: 100px;">
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="0.01" name="rates[{{ $order->id }}]" 
                                                   class="form-control text-center p-1" 
                                                   value="{{ $order->commission_rate }}">
                                            <span class="input-group-text p-1">%</span>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="fw-bold text-uppercase" style="background-color: #e2e3e5;">
                                <tr>
                                    <td colspan="4">TOTAL SALE / RETURN MONTH OF {{ strtoupper(\Carbon\Carbon::parse($selectedMonth)->format('F')) }}</td>
                                    <td class="text-end">{{ number_format($orders->sum('total_amount'), 2) }}</td>
                                    <td class="text-end">{{ number_format($totalCashCollection, 2) }}</td>
                                    <td class="text-end">{{ number_format($totalChequeCollection, 2) }}</td>
                                    <td class="text-end">{{ number_format($orders->sum('due_amount'), 2) }}</td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <!-- Calculation Section -->
                <div class="mt-4 p-3">
                    <h5 class="text-decoration-underline mb-3">COMMISSION CALCULATE -:</h5>
                    <div class="row">
                        <div class="col-md-7">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>1 TOTAL CASH COLLECTION</span>
                                <div>
                                    <span class="me-2">{{ number_format($totalCashCollection, 2) }}</span>
                                    * <input type="number" step="0.01" name="cash_rate" class="form-control d-inline-block text-center p-0 mx-1" style="width: 60px;" value="{{ $salesCommission->cash_rate ?? 0 }}"> %
                                    <span class="fw-bold ms-3">{{ number_format($totalCashCommission, 2) }}</span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>2 TOTAL CHEQUE COLLECTION</span>
                                <div>
                                    <span class="me-2">{{ number_format($totalChequeCollection, 2) }}</span>
                                    * <input type="number" step="0.01" name="cheque_rate" class="form-control d-inline-block text-center p-0 mx-1" style="width: 60px;" value="{{ $salesCommission->cheque_rate ?? 0 }}"> %
                                    <span class="fw-bold ms-3">{{ number_format($totalChequeCommission, 2) }}</span>
                                </div>
                            </div>
                            <hr style="border-top: 2px solid black;">
                            <div class="d-flex justify-content-between">
                                <strong>TOTAL</strong>
                                <strong style="text-decoration-line: underline; text-decoration-style: double;">{{ number_format($totalCommission, 2) }}</strong>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4 border-top pt-3">
                        <div class="col-md-6">
                            <h5 class="text-decoration-underline mb-3">PAYMENT STATUS</h5>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" id="payment_status" onchange="togglePaymentMethod()">
                                    <option value="pending" {{ ($salesCommission->status ?? 'pending') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="paid" {{ ($salesCommission->status ?? 'pending') == 'paid' ? 'selected' : '' }}>Paid</option>
                                </select>
                            </div>

                            <div class="mb-3" id="payment_method_div" style="display: {{ ($salesCommission->status ?? 'pending') == 'paid' ? 'block' : 'none' }};">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" class="form-select">
                                    <option value="">-- Select Method --</option>
                                    <option value="cash" {{ ($salesCommission->payment_method ?? '') == 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="bank_transfer" {{ ($salesCommission->payment_method ?? '') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                </select>
                            </div>

                            @php
                                $currentSalesPerson = $salesPersons->find($selectedSalesPersonId);
                            @endphp
                            @if($currentSalesPerson && $currentSalesPerson->bank_account_number)
                                <div class="alert alert-info">
                                    <strong>Bank Details:</strong> {{ $currentSalesPerson->bank_account_number }}
                                </div>
                            @endif
                        </div>
                    </div>
                </form>
@endcanRoute

                <script>
                    function togglePaymentMethod() {
                        var status = document.getElementById('payment_status').value;
                        var methodDiv = document.getElementById('payment_method_div');
                        if (status === 'paid') {
                            methodDiv.style.display = 'block';
                        } else {
                            methodDiv.style.display = 'none';
                        }
                    }
                </script>
        </div>
    @elseif($selectedSalesPersonId)
        <div class="alert alert-info">No orders found for the selected criteria.</div>
    @endif
</div>
@endsection
