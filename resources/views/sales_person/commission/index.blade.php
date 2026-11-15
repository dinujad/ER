@extends('sales_person.layout')

@section('title', 'My Commission - ' . config('app.name') . ' Sales')

@section('content')
<style>
    .commission-hero { background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%); color: #fff; border-radius: 16px; padding: 1.25rem 1.5rem; margin-bottom: 1.25rem; box-shadow: 0 10px 40px rgba(30,58,95,0.2); }
    .commission-hero h1 { font-weight: 700; font-size: 1.35rem; margin: 0; }
    .commission-hero .text-opacity-90 { opacity: 0.9; font-size: 0.875rem; }
    .sum-card { border: none; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); padding: 1.25rem; }
    .sum-card .sum-value { font-size: 1.5rem; font-weight: 700; }
    .sum-card .sum-label { font-size: 0.8rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.04em; }
    .section-card { border: none; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); overflow: hidden; }
    .section-card .card-header { background: #fff; border-bottom: 1px solid #eee; padding: 0.875rem 1.25rem; font-weight: 600; font-size: 0.95rem; color: #1a1a2e; }
    .section-card .card-header .bi { color: #4e73df; margin-right: 0.35rem; }
    .section-card .table { margin: 0; font-size: 0.9rem; }
    .section-card .table th { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.04em; color: #6c757d; font-weight: 600; padding: 0.75rem 1rem; }
    .section-card .table td { padding: 0.75rem 1rem; vertical-align: middle; }
    .section-card .table tbody tr:hover { background-color: #f8fafc; }
</style>

<div class="commission-hero">
    <h1><i class="bi bi-wallet2 me-2"></i> My Commission</h1>
    <p class="text-opacity-90 mb-0 mt-1">Pending (to be paid by admin) and history of commissions received</p>
</div>

{{-- Summary cards: Pending + Total earned (labuna) --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-md-4">
        <div class="card sum-card border-primary">
            <div class="sum-label">Pending Commission</div>
            <div class="sum-value text-primary">{{ number_format($pendingTotal, 2) }}</div>
            <small class="text-muted">Admin will pay this amount</small>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4">
        <div class="card sum-card border-success">
            <div class="sum-label">Total Earned (ලබුණු)</div>
            <div class="sum-value text-success">{{ number_format($totalEarned, 2) }}</div>
            <small class="text-muted">All time paid commission</small>
        </div>
    </div>
</div>

{{-- Commission history table --}}
<div class="card section-card">
    <div class="card-header">
        <span><i class="bi bi-clock-history"></i> Commission History</span>
    </div>
    <div class="card-body p-0">
        @if(empty($history))
            <div class="p-4 text-center text-muted">
                <i class="bi bi-inbox fs-1"></i>
                <p class="mb-0 mt-2">No commission records yet.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Paid date</th>
                            <th>Payment method</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history as $row)
                        <tr>
                            <td><strong>{{ $row->month_label }}</strong></td>
                            <td>{{ number_format($row->total_amount, 2) }}</td>
                            <td>
                                @if($row->status === 'paid')
                                    <span class="badge bg-success">Paid</span>
                                @else
                                    <span class="badge bg-secondary">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if($row->paid_at)
                                    {{ \Carbon\Carbon::parse($row->paid_at)->format('d M Y') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($row->payment_method)
                                    <span class="text-capitalize">{{ str_replace('_', ' ', $row->payment_method) }}</span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
