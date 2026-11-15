@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <a href="{{ route('jobs.index') }}" class="btn btn-sm btn-outline-secondary mb-2">← All Jobs</a>
            <h4 class="mb-0">Job Costs — {{ $job->job_number }}</h4>
            <p class="text-muted small mb-0">{{ $job->style_ref }} · {{ \App\Models\ManufacturingJob::statusLabel($job->status) }}</p>
        </div>
        <a href="{{ route('jobs.show', $job->id) }}" class="btn btn-sm btn-primary">Job details</a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Cost breakdown</h6>
                </div>
                <div class="card-body">
                    @include('erp.jobs.partials.cost-breakdown', ['job' => $job, 'breakdown' => $breakdown, 'showActions' => true])
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4" id="add-cost">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">Add production cost</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted">Add sewing, washing, transport, labour or other costs not captured from material stock.</p>
                    <form method="POST" action="{{ route('jobs.costs.store', $job->id) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Cost type</label>
                            <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                                @foreach($categories as $key => $label)
                                    <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">What is this cost for?</label>
                            <input type="text" name="description" class="form-control @error('description') is-invalid @enderror"
                                   value="{{ old('description') }}" placeholder="e.g. External embroidery, factory washing charge" required maxlength="500">
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount (LKR)</label>
                            <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror"
                                   value="{{ old('amount') }}" step="0.01" min="0.01" required>
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-success w-100">Add cost</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
