@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <a href="{{ route('supervisor.dashboard') }}" class="text-decoration-none small text-secondary"><i class="bi bi-arrow-left me-1"></i> Dashboard</a>
        <h4 class="mb-0 mt-1">Record ingredient usage — {{ $job->job_number }}</h4>
        <p class="text-muted small mb-0">Section: {{ $section->name }}. Enter how much was used from your section (not from recipe selection in manufacturing).</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form action="{{ route('supervisor.usage.store', $job) }}" method="POST">
        @csrf
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Ingredients for your section's stage</h6>
            </div>
            <div class="card-body">
                @foreach($recipe->steps as $step)
                    <div class="mb-4">
                        <h6 class="text-primary">{{ $step->name }}</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Ingredient</th>
                                        <th>Planned (recipe)</th>
                                        <th>Already recorded</th>
                                        <th>Quantity used (enter)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($step->ingredients as $ing)
                                        @php
                                            $key = $step->id . '_' . $ing->material_id;
                                            $existing = $existingIssues->get($key);
                                            $alreadyQty = $existing ? (float) $existing->quantity_issued : 0;
                                        @endphp
                                        <tr>
                                            <td>{{ $ing->material->name ?? '—' }} ({{ $ing->material->unit ?? 'pcs' }})</td>
                                            <td>{{ number_format($ing->quantity, 4) }}</td>
                                            <td>{{ number_format($alreadyQty, 4) }}</td>
                                            <td style="max-width: 120px;">
                                                <input type="hidden" name="usage[{{ $step->id }}-{{ $ing->material_id }}][recipe_step_id]" value="{{ $step->id }}">
                                                <input type="hidden" name="usage[{{ $step->id }}-{{ $ing->material_id }}][material_id]" value="{{ $ing->material_id }}">
                                                <input type="number" name="usage[{{ $step->id }}-{{ $ing->material_id }}][quantity_used]" class="form-control form-control-sm" step="0.0001" min="0" value="{{ old('usage.'.$step->id.'-'.$ing->material_id.'.quantity_used', $alreadyQty) }}" placeholder="0">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Save usage</button>
                <a href="{{ route('supervisor.dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
