@extends('erp.layout')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Vehicle Run Summary</h1>

    <div class="card shadow mb-4">
        <div class="card-header">Filter</div>
        <div class="card-body">
            <form method="GET" action="{{ route('vehicle-runs.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Vehicle</label>
                    <select name="vehicle_id" class="form-select">
                        <option value="">— Select —</option>
                        @foreach($vehicles as $v)
                            <option value="{{ $v->id }}" {{ $vehicleId == $v->id ? 'selected' : '' }}>{{ $v->number_plate }} ({{ $v->name }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Driver</label>
                    <select name="driver_id" class="form-select">
                        <option value="">— Select —</option>
                        @foreach($drivers as $d)
                            <option value="{{ $d->id }}" {{ $driverId == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-control" value="{{ $date }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">View Summary</button>
                </div>
            </form>
        </div>
    </div>

    @if($run)
    <div class="card shadow mb-4">
        <div class="card-header">Daily Run Summary — {{ $run->run_date->format('d M Y') }}</div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6"><strong>Vehicle:</strong> {{ $run->vehicle->name }} ({{ $run->vehicle->number_plate }})</div>
                <div class="col-md-6"><strong>Driver:</strong> {{ $run->driver->name }}</div>
            </div>
            <table class="table table-bordered">
                <tr><th width="200">Start KM</th><td>{{ $run->start_km ?? '—' }}</td></tr>
                <tr><th>End KM</th><td>{{ $run->end_km ?? '—' }}</td></tr>
                <tr><th>Route (From)</th><td>{{ $run->start_route ?? '—' }}</td></tr>
                <tr><th>Route (To)</th><td>{{ $run->end_route ?? '—' }}</td></tr>
                <tr><th>Distance (KM)</th><td>{{ $run->distance_km ?? '—' }}</td></tr>
                <tr><th>Google Map Link</th><td>@if($run->google_map_link)<a href="{{ $run->google_map_link }}" target="_blank" rel="noopener">Open map</a>@else — @endif</td></tr>
                <tr><th>Notes</th><td>{{ $run->notes ?? '—' }}</td></tr>
            </table>
            @if($run->items->isNotEmpty())
            <h6 class="mt-3">Costs & slips</h6>
            <table class="table table-bordered table-sm">
                <thead><tr><th>Type</th><th>Name</th><th>Cost (Rs)</th><th>Slip</th></tr></thead>
                <tbody>
                    @foreach($run->items as $item)
                    <tr>
                        <td>{{ ucfirst($item->type) }}</td>
                        <td>{{ $item->name }}</td>
                        <td>{{ number_format($item->cost, 2) }}</td>
                        <td>@include('partials.expense-receipt-link', ['item' => $item])</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <p><strong>Total cost: Rs {{ number_format($run->items->sum('cost'), 2) }}</strong></p>
            @endif
            <div class="row mt-3">
                @if($run->start_meter_photo_path ?? null)
                    <div class="col-md-4">
                        <strong>Start meter photo</strong><br>
                        <a href="{{ asset('storage/'.$run->start_meter_photo_path) }}" target="_blank"><img src="{{ asset('storage/'.$run->start_meter_photo_path) }}" alt="Start meter" class="img-fluid rounded" style="max-height:200px"></a>
                    </div>
                @endif
                @if($run->meter_photo_path)
                    <div class="col-md-4">
                        <strong>End meter photo</strong><br>
                        <a href="{{ asset('storage/'.$run->meter_photo_path) }}" target="_blank"><img src="{{ asset('storage/'.$run->meter_photo_path) }}" alt="End meter" class="img-fluid rounded" style="max-height:200px"></a>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @elseif($vehicleId && $driverId && $date)
    <div class="alert alert-info">No run record for selected vehicle, driver and date.</div>
    @endif
</div>
@endsection
