@extends('erp.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Salary Components</h4>
    <a href="{{ route('salary-components.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Component</a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Name</th><th>Code</th><th>Type</th><th>Calculation</th><th>Value</th><th>Active</th><th width="100"></th></tr>
            </thead>
            <tbody>
                @forelse($components as $c)
                <tr>
                    <td>{{ $c->name }}</td>
                    <td>{{ $c->code ?? '—' }}</td>
                    <td><span class="badge {{ $c->type === 'allowance' ? 'bg-success' : 'bg-warning text-dark' }}">{{ ucfirst($c->type) }}</span></td>
                    <td>{{ $c->calculation_type === 'percentage' ? 'Percentage' : 'Fixed' }}</td>
                    <td>{{ $c->calculation_type === 'percentage' ? $c->default_value . '%' : number_format($c->default_value, 2) }}</td>
                    <td>{{ $c->is_active ? 'Yes' : 'No' }}</td>
                    <td>@canRoute('salary-components.edit')
<a href="{{ route('salary-components.edit', $c) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
@endcanRoute
</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No components. <a href="{{ route('salary-components.create') }}">Add allowances/deductions</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $components->links() }}</div>
</div>
@endsection
