@extends('driver.layout')

@section('title', 'Job summary - ' . $run->run_date->format('d M Y'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Job — {{ $run->run_date->format('d M Y') }}</h1>
    <a href="{{ route('driver.jobs.index') }}" class="btn btn-outline-secondary">← My Jobs</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

{{-- Summary --}}
<div class="card mb-4">
    <div class="card-header">Summary</div>
    <div class="card-body">
        <div class="row mb-2">
            <div class="col-md-6"><strong>Vehicle:</strong> {{ $run->vehicle?->name }} ({{ $run->vehicle?->number_plate }})</div>
            <div class="col-md-6"><strong>Route:</strong> {{ $run->start_route ?? '—' }} → {{ $run->end_route ?? '—' }}</div>
        </div>
        <div class="row mb-2">
            <div class="col-md-6"><strong>Start meter:</strong> @if($run->start_meter_photo_path)<a href="{{ asset('storage/'.$run->start_meter_photo_path) }}" target="_blank">View photo</a>@else — @endif</div>
            <div class="col-md-6"><strong>End meter:</strong> @if($run->meter_photo_path)<a href="{{ asset('storage/'.$run->meter_photo_path) }}" target="_blank">View photo</a>@else — @endif</div>
        </div>
        <div class="row mb-2">
            <div class="col-md-6"><strong>Distance (KM):</strong> {{ $run->distance_km ?? '—' }}</div>
            <div class="col-md-6"><strong>Google Map:</strong> @if($run->google_map_link)<a href="{{ $run->google_map_link }}" target="_blank">Open</a>@else — @endif</div>
        </div>
        @if($run->notes)
            <p class="mb-0"><strong>Notes:</strong> {{ $run->notes }}</p>
        @endif
    </div>
</div>

@if($run->status === 'started')
    <div class="alert alert-info">This job is still active. Add expenses below or from <a href="{{ route('driver.dashboard') }}#expenses">Dashboard</a>.</div>
    @include('driver.partials.expense-form', [
        'run' => $run,
        'redirectTo' => route('driver.jobs.show', $run) . '#expenses',
    ])
@endif

<div class="card mb-4" id="expenses">
    <div class="card-header">Expenses & slips</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead><tr><th>Type</th><th>Name</th><th>Cost</th><th>Slip</th></tr></thead>
            <tbody>
                @foreach($run->items as $item)
                <tr>
                    <td>{{ ucfirst($item->type) }}</td>
                    <td>{{ $item->name }}</td>
                    <td>Rs {{ number_format($item->cost, 2) }}</td>
                    <td>@include('partials.expense-receipt-link', ['item' => $item])</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($run->items->isNotEmpty())
            <div class="p-2 border-top"><strong>Total: Rs {{ number_format($run->items->sum('cost'), 2) }}</strong></div>
        @endif
    </div>
</div>

{{-- Edit form: slip & cost --}}
<div class="card">
    <div class="card-header">Edit slips & costs</div>
    <div class="card-body">
        @canRoute('driver.jobs.update')
<form method="POST" action="{{ route('driver.jobs.update', $run) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="row g-2 mb-3">
                <div class="col-md-6"><label class="form-label">Start route</label><input type="text" class="form-control" name="start_route" value="{{ old('start_route', $run->start_route) }}"></div>
                <div class="col-md-6"><label class="form-label">End route</label><input type="text" class="form-control" name="end_route" value="{{ old('end_route', $run->end_route) }}"></div>
                <div class="col-md-6"><label class="form-label">End KM</label><input type="number" step="0.01" class="form-control" name="end_km" value="{{ old('end_km', $run->end_km) }}"></div>
                <div class="col-md-6"><label class="form-label">Distance (KM)</label><input type="number" step="0.01" class="form-control" name="distance_km" value="{{ old('distance_km', $run->distance_km) }}"></div>
                <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2">{{ old('notes', $run->notes) }}</textarea></div>
            </div>
            @if($run->items->isNotEmpty())
            <h6 class="border-bottom pb-1">Item costs & slips</h6>
            @foreach($run->items as $index => $item)
            <div class="row g-2 mb-2 align-items-end">
                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                <div class="col-md-4"><label class="form-label small">{{ $item->name }}</label><input type="number" step="0.01" class="form-control form-control-sm" name="items[{{ $index }}][cost]" value="{{ old('items.'.$index.'.cost', $item->cost) }}" min="0"></div>
                <div class="col-md-4"><label class="form-label small">New slip (optional)</label><input type="file" class="form-control form-control-sm" name="items[{{ $index }}][slip]" accept=".pdf,.jpg,.jpeg,.png"></div>
            </div>
            @endforeach
            @endif
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
        </form>
@endcanRoute
    </div>
</div>
@endsection
