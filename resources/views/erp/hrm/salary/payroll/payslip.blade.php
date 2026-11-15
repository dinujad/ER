<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - {{ $payrollRun->title }} - {{ $item->employee?->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
        }
        body { font-size: 14px; color: #333; }
        .payslip-header { border-bottom: 2px solid #0d6efd; padding-bottom: 1rem; margin-bottom: 1rem; }
        .company-logo { max-height: 60px; max-width: 180px; object-fit: contain; }
        .amount-words { font-style: italic; color: #555; }
        .table-payslip th { background: #f8f9fa; font-weight: 600; }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            {{-- Company header with logo --}}
            <div class="d-flex justify-content-between align-items-start payslip-header">
                <div>
                    @if(file_exists(public_path('images/logo.png')))
                        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="company-logo mb-2">
                    @endif
                    <h5 class="mb-0 fw-bold">{{ config('app.name') }}</h5>
                    @if(config('app.business_address'))
                        <p class="small text-muted mb-0">{{ config('app.business_address') }}</p>
                    @endif
                </div>
                <div class="text-end">
                    <h5 class="mb-0">Payslip</h5>
                    <p class="mb-0 fw-bold">Payslip for the month of {{ $payrollRun->period_start->format('F Y') }}</p>
                </div>
            </div>

            {{-- Employee info --}}
            <div class="row mb-3">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted" style="width: 40%;">Employee:</td><td><strong>{{ $item->employee?->name ?? '—' }}</strong></td></tr>
                        <tr><td class="text-muted">Department:</td><td>{{ $item->employee?->department?->name ?? '—' }}</td></tr>
                        <tr><td class="text-muted">Designation:</td><td>{{ $item->employee?->designation?->title ?? $item->employee?->role ?? '—' }}</td></tr>
                        <tr><td class="text-muted">Primary work location:</td><td>Main factory</td></tr>
                    </table>
                </div>
            </div>

            {{-- Work & attendance summary --}}
            <div class="mb-3">
                <h6 class="border-bottom pb-1 mb-2">Work and attendance summary</h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width: 22%;">Total work duration:</td>
                        <td><strong>{{ $item->work_duration ?? 1 }}</strong> {{ $item->work_duration_unit ?? 'Month' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Days present:</td>
                        <td><strong>{{ $item->days_present ?? 0 }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Days absent:</td>
                        <td><strong>{{ $item->days_absent ?? 0 }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Days leave:</td>
                        <td><strong>{{ $item->leave_days ?? 0 }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Overtime:</td>
                        <td><strong>{{ number_format(($item->ot_minutes ?? 0) / 60, 1) }} h</strong> · Rs {{ number_format($item->ot_amount ?? 0, 2) }}</td>
                    </tr>
                </table>
            </div>

            {{-- Earnings --}}
            <div class="mb-3">
                <h6 class="border-bottom pb-1 mb-2">Earnings</h6>
                <table class="table table-sm table-payslip mb-0">
                    <tbody>
                        <tr>
                            <td>Basic salary</td>
                            <td class="text-end">Rs {{ number_format($item->basic_salary, 2) }}</td>
                            <td class="text-muted small">({{ $item->work_duration ?? 1 }} {{ $item->work_duration_unit ?? 'Month' }} × {{ number_format($item->amount_per_unit ?? 0, 2) }})</td>
                        </tr>
                        @if(($item->ot_amount ?? 0) > 0)
                        <tr>
                            <td>Overtime</td>
                            <td class="text-end">Rs {{ number_format($item->ot_amount, 2) }}</td>
                            <td class="text-muted small">{{ number_format(($item->ot_minutes ?? 0) / 60, 1) }} hours from shift</td>
                        </tr>
                        @endif
                        @if($item->details)
                            @foreach($item->details as $d)
                                @if(($d['type'] ?? '') === 'allowance')
                                    <tr><td>{{ $d['name'] }}</td><td class="text-end">Rs {{ number_format($d['value'] ?? 0, 2) }}</td><td></td></tr>
                                @endif
                            @endforeach
                        @endif
                        <tr class="fw-bold"><td>Total earnings</td><td class="text-end">Rs {{ number_format($item->gross_salary, 2) }}</td><td></td></tr>
                    </tbody>
                </table>
            </div>

            {{-- Deductions --}}
            <div class="mb-3">
                <h6 class="border-bottom pb-1 mb-2">Deductions</h6>
                <table class="table table-sm table-payslip mb-0">
                    <tbody>
                        @if($item->details)
                            @foreach($item->details as $d)
                                @if(($d['type'] ?? '') === 'deduction')
                                    <tr><td>{{ $d['name'] }}</td><td class="text-end">Rs {{ number_format($d['value'] ?? 0, 2) }}</td></tr>
                                @endif
                            @endforeach
                        @endif
                        <tr class="fw-bold"><td>Total deductions</td><td class="text-end">Rs {{ number_format($item->total_deductions, 2) }}</td></tr>
                    </tbody>
                </table>
            </div>

            {{-- Net pay --}}
            <div class="alert alert-primary py-2 mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Net pay</span>
                    <span class="fw-bold fs-5">Rs {{ number_format($item->net_salary, 2) }}</span>
                </div>
                <p class="mb-0 amount-words small mt-1">In words: {{ $netSalaryInWords ?? 'Rs ' . number_format($item->net_salary, 2) . ' only.' }}</p>
            </div>

            @if($item->note)
                <p class="small mb-2"><strong>Note:</strong> {{ $item->note }}</p>
            @endif

            <div class="small text-muted text-center border-top pt-3 mt-3">
                This is a computer-generated payslip. Generated on {{ now()->format('d M Y H:i') }}.
            </div>
        </div>
    </div>
    <div class="text-center mt-3 no-print">
        <button onclick="window.print()" class="btn btn-primary">Print paysheet</button>
        <a href="{{ route('payroll-runs.show', $payrollRun) }}" class="btn btn-outline-secondary">Back</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
