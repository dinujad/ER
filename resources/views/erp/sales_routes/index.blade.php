@extends('erp.layout')

@section('title', 'Sales Routes')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">Sales Routes</h1>
            <p class="text-muted small mb-0">Create named routes, assign sales persons — they enter driver & assistants when starting a trip.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('sales-routes.live') }}" class="btn btn-outline-success btn-sm">Started routes</a>
            <a href="{{ route('sales-routes.runs') }}" class="btn btn-outline-secondary btn-sm">All routes</a>
            <a href="{{ route('sales-routes.create') }}" class="btn btn-primary btn-sm">Add Route</a>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Sales persons</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($routes as $route)
                        <tr>
                            <td class="fw-semibold">{{ $route->name }}</td>
                            <td class="text-muted">{{ $route->code ?: '—' }}</td>
                            <td>{{ $route->sales_persons_count }}</td>
                            <td>
                                @if($route->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @canRoute('sales-routes.edit')
<a href="{{ route('sales-routes.edit', $route) }}" class="btn btn-sm btn-outline-primary">Edit</a>
@endcanRoute
                                @canRoute('sales-routes.destroy')
<form action="{{ route('sales-routes.destroy', $route) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this route?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
@endcanRoute
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No routes yet. Add one to assign sales persons.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($routes->hasPages())
            <div class="card-footer">{{ $routes->links() }}</div>
        @endif
    </div>
</div>
@endsection
