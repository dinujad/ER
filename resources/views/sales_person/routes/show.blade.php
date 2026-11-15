@extends('sales_person.layout')

@section('title', 'My Route - ' . config('app.name'))

@section('content')
<style>
    .route-hero { background: linear-gradient(135deg, #1e3a5f 0%, #2d6a9f 100%); color: #fff; border-radius: 16px; padding: 1.25rem 1.5rem; margin-bottom: 1.25rem; }
    .route-path { font-size: 1.35rem; font-weight: 700; letter-spacing: -0.02em; }
    .live-dot { width: 10px; height: 10px; border-radius: 50%; background: #22c55e; display: inline-block; box-shadow: 0 0 0 4px rgba(34,197,94,.25); }
</style>

<div class="route-hero">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <div class="small text-white-50 mb-1">My assigned route</div>
            @if($route)
                <div class="route-path">{{ $route->name }}</div>
                @if($route->code)
                    <div class="small mt-1 opacity-90">{{ $route->code }}</div>
                @endif
            @else
                <div class="route-path">No route assigned</div>
                <div class="small mt-1 opacity-90">Ask admin to assign a sales route.</div>
            @endif
        </div>
        @if($activeRun)
            <div class="text-end">
                <div class="small"><span class="live-dot me-1"></span> Started</div>
                <div class="fw-semibold" id="route-elapsed" data-started="{{ $activeRun->started_at?->timestamp ?? 0 }}">{{ $activeRun->durationLabel() }}</div>
                <div class="small opacity-90">{{ $activeRun->startedLabel('h:i A') }}</div>
            </div>
        @endif
    </div>
</div>

@if($activeRun && !empty($routeCash))
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3 text-center">
                <div class="col-6 col-md-3"><div class="small text-muted">Petty cash</div><div class="fw-bold">Rs {{ number_format($routeCash->petty, 2) }}</div></div>
                <div class="col-6 col-md-3"><div class="small text-muted">Cash collected</div><div class="fw-bold">Rs {{ number_format($routeCash->collected, 2) }}</div></div>
                <div class="col-6 col-md-3"><div class="small text-muted">Expenses</div><div class="fw-bold">Rs {{ number_format($routeCash->expenses, 2) }}</div></div>
                <div class="col-6 col-md-3"><div class="small text-muted">Handover to office</div><div class="fw-bold {{ $routeCash->handover < 0 ? 'text-danger' : 'text-success' }}">Rs {{ number_format($routeCash->handover, 2) }}</div></div>
            </div>
        </div>
    </div>
@endif

@if($activeRun)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="text-muted small">Driver</div>
                    <div class="fw-semibold">{{ $activeRun->driver_name ?: '—' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small">Assistants</div>
                    <div class="fw-semibold">{{ $activeRun->assistants ?: '—' }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('sales-person.route.complete') }}" onsubmit="if (!confirm('Complete this route now?')) return false; this.querySelector('button').disabled = true;">
                @csrf
                <button type="submit" class="btn btn-outline-dark">
                    <i class="bi bi-check2-circle me-1"></i> Complete route
                </button>
            </form>
        </div>
    </div>
@elseif($route && $route->is_active)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="POST" action="{{ route('sales-person.route.start') }}" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Driver name <span class="text-danger">*</span></label>
                    <input type="text" name="driver_name" class="form-control @error('driver_name') is-invalid @enderror" value="{{ old('driver_name') }}" required placeholder="Who is driving with you today?">
                    @error('driver_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Assistants</label>
                    <textarea name="assistants" class="form-control @error('assistants') is-invalid @enderror" rows="2" placeholder="Type all assistants (comma separated)">{{ old('assistants') }}</textarea>
                    @error('assistants')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Example: Kamal, Nimal, Sunil</div>
                </div>
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="bi bi-play-circle me-1"></i> Start route
                </button>
            </form>
        </div>
    </div>
@elseif($route)
    <div class="alert alert-warning mb-3">This route is inactive. Ask admin to activate it before starting.</div>
@endif

@if($activeRun)
    <div class="alert alert-success">
        Route started at <strong>{{ $activeRun->startedLabel('h:i A, d M Y') }}</strong>.
        Add expenses from this page or your dashboard — they attach to this trip.
    </div>

    @php $expenseRedirect = route('sales-person.route.show', [], false) . '#expenses'; @endphp
    @include('sales_person.partials.expense-form', ['redirectTo' => $expenseRedirect])

    @if($activeRun->expenses->isNotEmpty())
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">This trip expenses (Rs {{ number_format((float) $activeRun->expenses->sum('amount'), 2) }})</div>
            <ul class="list-group list-group-flush">
                @foreach($activeRun->expenses->sortByDesc('id') as $ex)
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ $ex->typeLabel() }} · {{ $ex->description }}</span>
                        <strong>Rs {{ number_format((float) $ex->amount, 2) }}</strong>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@elseif($route && $route->is_active)
    <div class="alert alert-warning">Enter driver and assistants, then start the route to record trip time and add expenses.</div>
@endif

@if($recentRuns->isNotEmpty())
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">Recent trips</div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Route</th>
                        <th>Driver</th>
                        <th>Assistants</th>
                        <th>Started</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th class="text-end">Expenses</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentRuns as $run)
                        <tr>
                            <td>{{ $run->salesRoute?->name ?? '—' }}</td>
                            <td>{{ $run->driver_name ?: '—' }}</td>
                            <td>{{ $run->assistants ?: '—' }}</td>
                            <td>{{ $run->startedLabel('d M, h:i A') }}</td>
                            <td>{{ $run->durationLabel() }}</td>
                            <td>
                                @if($run->isActive())
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Completed</span>
                                @endif
                            </td>
                            <td class="text-end">Rs {{ number_format((float) $run->expenses->sum('amount'), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection

@section('scripts')
<script>
(function () {
    var el = document.getElementById('route-elapsed');
    if (!el) return;
    var started = parseInt(el.getAttribute('data-started'), 10) * 1000;
    if (!started) return;
    function tick() {
        var sec = Math.max(0, Math.floor((Date.now() - started) / 1000));
        var h = Math.floor(sec / 3600);
        var m = Math.floor((sec % 3600) / 60);
        el.textContent = h > 0 ? (h + 'h ' + m + 'm') : (m + 'm');
    }
    tick();
    setInterval(tick, 30000);
})();
</script>
@endsection
