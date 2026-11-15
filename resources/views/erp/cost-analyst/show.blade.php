@extends('erp.layout')

@section('content')
@php
    $a = $data['actual'] ?? [];
    $p = $data['planned'] ?? [];
    $v = $data['variance'] ?? [];
    $adv = $data['advisory'] ?? [];
    $sales = $data['sales_history'] ?? [];
    $margin = $data['margin_percent'] ?? $defaultMargin;
    $drivers = $adv['cost_drivers'] ?? [];
@endphp
<div class="container-fluid py-4">
    <div class="mb-3">
        <a href="{{ route('cost-analyst.index', request()->only('from', 'to', 'margin')) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Cost Analyst</a>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">{{ $job->job_number }} · {{ $job->product?->name ?? 'Product' }}</h4>
            <p class="text-muted small mb-0">Job-wise cost analysis · sell price suggestion · sales history chart</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('manufacturing.final.detail', $job->id) }}" class="btn btn-sm btn-outline-primary">Final Checking</a>
            <a href="{{ route('jobs.costs', $job->id) }}" class="btn btn-sm btn-outline-secondary">Manage costs</a>
        </div>
    </div>

    <form method="GET" class="card mb-4">
        <div class="card-body py-3">
            <label class="form-label small mb-1">Profit margin % (laba): <strong id="marginLabel">{{ $margin }}%</strong></label>
            <div class="row g-2 align-items-center">
                <div class="col-md-8">
                    <input type="range" name="margin" class="form-range" min="5" max="100" step="1" value="{{ $margin }}"
                           oninput="document.getElementById('marginLabel').textContent = this.value + '%'">
                </div>
                <div class="col-md-4"><button type="submit" class="btn btn-primary btn-sm">Recalculate price</button></div>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card h-100 border-primary">
                <div class="card-body text-center">
                    <div class="small text-muted">Actual cost / pc</div>
                    <div class="fs-4 fw-bold text-primary">Rs {{ number_format($a['per_unit'] ?? 0, 2) }}</div>
                    <small class="text-muted">incl. overhead</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-success bg-success bg-opacity-10">
                <div class="card-body text-center">
                    <div class="small text-muted">Suggested sell ({{ $margin }}% margin)</div>
                    <div class="fs-4 fw-bold text-success">Rs {{ number_format($data['suggested_sell_price'] ?? 0, 2) }}</div>
                    <small>Profit Rs {{ number_format($data['suggested_profit_per_unit'] ?? 0, 2) }}/pc</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="small text-muted">Recipe planned / pc</div>
                    <div class="fs-5 fw-bold">Rs {{ number_format($p['per_unit'] ?? 0, 2) }}</div>
                    <small class="{{ ($v['per_unit'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                        Variance Rs {{ number_format($v['per_unit'] ?? 0, 2) }}/pc
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="small text-muted">Past avg sell price</div>
                    <div class="fs-5 fw-bold">Rs {{ $data['historical_avg_sell'] ? number_format($data['historical_avg_sell'], 2) : '—' }}</div>
                    <small class="text-muted">From order history</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header"><strong>Past sales — this product</strong></div>
                <div class="card-body">
                    @if(!empty($sales['labels']))
                        <canvas id="salesChart" height="120"></canvas>
                    @else
                        <p class="text-muted small mb-0">No sales history yet for this product. After first sales, chart will show qty &amp; average price by month.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><strong>Cost drivers / pc</strong></div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($drivers as $d)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold small">{{ $d['label'] }}</div>
                                    <div class="text-muted" style="font-size:.75rem">{{ $d['hint'] ?? '' }}</div>
                                </div>
                                <div class="text-end">
                                    <div>Rs {{ number_format($d['amount'], 2) }}</div>
                                    <span class="badge bg-secondary">{{ $d['percent'] }}%</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            @include('erp.manufacturing.partials.final-cost-report', ['finalCost' => $data])
        </div>
        <div class="col-lg-6">
            <div class="card border-info h-100">
                <div class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-lightbulb me-1"></i> Advisor &amp; insights</strong>
                    <span class="badge {{ ($adv['source'] ?? '') === 'ai+rules' ? 'bg-primary' : 'bg-secondary' }}">
                        {{ ($adv['source'] ?? '') === 'ai+rules' ? 'AI + Rules' : 'Smart rules' }}
                    </span>
                </div>
                <div class="card-body">
                    <p class="small fw-semibold">{{ $adv['summary'] ?? '' }}</p>
                    @foreach($adv['insights'] ?? [] as $ins)
                        <div class="alert alert-{{ $ins['level'] === 'danger' ? 'danger' : ($ins['level'] === 'warning' ? 'warning' : ($ins['level'] === 'success' ? 'success' : 'light')) }} py-2 px-3 small mb-2">
                            <strong>{{ $ins['title'] }}</strong><br>{{ $ins['body'] }}
                        </div>
                    @endforeach
                    @if(!empty($adv['ai_plan']))
                        <div class="border rounded p-3 bg-light mt-3">
                            <div class="fw-semibold mb-2"><i class="bi bi-robot"></i> AI pricing plan</div>
                            <div class="small" style="white-space: pre-wrap;">{{ $adv['ai_plan'] }}</div>
                        </div>
                    @else
                        <p class="text-muted small mb-0 mt-2">Add <code>GEMINI_API_KEY</code> to .env for full AI plan (electricity, salary, sell price advice).</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if(!empty($sales['labels']))
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('salesChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($sales['labels']),
            datasets: [
                {
                    label: 'Qty sold',
                    data: @json($sales['quantities']),
                    backgroundColor: 'rgba(30, 58, 95, 0.6)',
                    yAxisID: 'y',
                },
                {
                    label: 'Avg price (Rs)',
                    data: @json($sales['avg_prices']),
                    type: 'line',
                    borderColor: '#198754',
                    backgroundColor: '#198754',
                    yAxisID: 'y1',
                    tension: 0.3,
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, position: 'left', title: { display: true, text: 'Quantity' } },
                y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Rs' } }
            }
        }
    });
});
</script>
@endif
@endsection
