@extends('driver.layout')

@section('title', 'Daily Run - ' . config('app.name') . ' Driver')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h4 mb-0">Daily Run — {{ $today }}</h1>
    <span class="text-muted">{{ $driver->name }} @if($vehicle)<br><small>{{ $vehicle->name }} ({{ $vehicle->number_plate }})</small>@endif</span>
</div>

@if(!$vehicle)
    <div class="alert alert-warning">ඔයාට අද දිනට vehicle assign කරලා නැහැ. Admin එකෙන් Vehicle Management → Edit → Daily driver assignment එකෙන් assign කරන්න කියන්න.</div>
@elseif(!$run)
    <div class="card mb-4" id="expenses">
        <div class="card-header bg-secondary text-white"><i class="bi bi-receipt me-2"></i>Expenses</div>
        <div class="card-body">
            <p class="text-muted mb-3">Expenses record කරන්න පළමුව <strong>job එක start</strong> කරන්න.</p>
            @if($todayEndedCount > 0)
                <p class="text-success small mb-2">Today you completed {{ $todayEndedCount }} job(s).</p>
            @endif
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Start new job</div>
        <div class="card-body">
            <p class="text-muted">Upload start meter photo and enter start point to begin this job.</p>
            <form method="POST" action="{{ route('driver.start-day') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Start meter photo <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" name="start_meter_photo" accept="image/*" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Start point <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="start_point" value="{{ old('start_point') }}" placeholder="e.g. Colombo office" required>
                </div>
                <button type="submit" class="btn btn-primary">Start job</button>
            </form>
        </div>
    </div>
@elseif($run->status === 'started')
    <div class="card mb-3">
        <div class="card-header bg-success text-white">Current job</div>
        <div class="card-body">
            <p class="mb-1"><strong>Start point:</strong> {{ $run->start_route ?? '—' }}</p>
            @if($run->start_meter_photo_path)
                <p class="mb-0">Start meter: <a href="{{ asset('storage/'.$run->start_meter_photo_path) }}" target="_blank" rel="noopener">View photo</a></p>
            @endif
        </div>
    </div>

    @include('driver.partials.expense-form', ['run' => $run, 'redirectTo' => route('driver.dashboard') . '#expenses'])
    @include('driver.partials.expense-list', ['run' => $run, 'redirectTo' => route('driver.dashboard') . '#expenses'])

    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">End job</div>
        <div class="card-body">
            <p class="text-muted">Destination සහ end meter photo දාලා job එක close කරන්න.</p>
            <form method="POST" action="{{ route('driver.end-day') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="vehicle_daily_run_id" value="{{ $run->id }}">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Destination <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="destination" value="{{ old('destination', $run->end_route) }}" placeholder="e.g. Kandy" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">End meter photo <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="end_meter_photo" accept="image/*" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">End KM</label>
                        <input type="number" step="0.01" class="form-control" name="end_km" value="{{ old('end_km', $run->end_km) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Distance (KM)</label>
                        <input type="number" step="0.01" class="form-control" name="distance_km" value="{{ old('distance_km', $run->distance_km) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Google Map link</label>
                        <input type="url" class="form-control" name="google_map_link" value="{{ old('google_map_link', $run->google_map_link) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2">{{ old('notes', $run->notes) }}</textarea>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-secondary">End job & save</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection
