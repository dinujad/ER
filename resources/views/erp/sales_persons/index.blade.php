@extends('erp.layout')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Sales Persons</h1>
        <a href="{{ route('sales-persons.create') }}" class="btn btn-primary">Add Sales Person</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Sales Person List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Route</th>
                            <th>Customers</th>
                            <th>Orders</th>
                            <th>Joined At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($salesPersons as $person)
                        @php
                            $hasData = $person->customers_count > 0 || $person->orders_count > 0;
                            $others = $salesPersons->where('id', '!=', $person->id);
                        @endphp
                        <tr>
                            <td>{{ $person->name }}</td>
                            <td>{{ $person->email }}</td>
                            <td>{{ $person->phone ?? '—' }}</td>
                            <td>
                                @if($person->salesRoute)
                                    <span class="badge bg-info text-dark">{{ $person->salesRoute->name }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($person->customers_count > 0)
                                    <a href="{{ route('erp.customers.index', ['tab' => 'wholesale', 'added_by' => $person->id]) }}">{{ $person->customers_count }}</a>
                                @else
                                    0
                                @endif
                            </td>
                            <td>{{ $person->orders_count }}</td>
                            <td>{{ $person->created_at->format('Y-m-d') }}</td>
                            <td class="text-nowrap">
                                @canRoute('sales-persons.edit')
<a href="{{ route('sales-persons.edit', $person->id) }}" class="btn btn-sm btn-primary">Edit</a>
@endcanRoute
                                @canRoute('sales-persons.assign-route')
                                <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#assignRouteModal{{ $person->id }}">
                                    Assign Route
                                </button>
                                @endcanRoute
                                @canRoute('sales-persons.resend')
                                <form action="{{ route('sales-persons.resend', $person->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Generate a new password and send via SMS?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-warning" {{ !$person->phone ? 'disabled title=Add phone on edit first' : '' }}>
                                        <i class="bi bi-chat-dots"></i>
                                    </button>
                                </form>
                                @endcanRoute
                                @canRoute('sales-persons.destroy')
                                @if($hasData && $others->isEmpty())
                                    <button type="button" class="btn btn-sm btn-outline-danger" disabled title="Add another sales person to reassign customers/orders">Delete</button>
                                @elseif($hasData)
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $person->id }}">Delete</button>
                                @else
                                    @canRoute('sales-persons.destroy')
<form action="{{ route('sales-persons.destroy', $person->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete {{ $person->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
@endcanRoute
                                @endif
                                @endcanRoute
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@foreach($salesPersons as $person)
    @if(($person->customers_count > 0 || $person->orders_count > 0) && $salesPersons->where('id', '!=', $person->id)->isNotEmpty())
    <div class="modal fade" id="deleteModal{{ $person->id }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $person->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                @canRoute('sales-persons.destroy')
<form method="POST" action="{{ route('sales-persons.destroy', $person->id) }}">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel{{ $person->id }}">Delete {{ $person->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>This sales person has <strong>{{ $person->customers_count }}</strong> customer(s) and <strong>{{ $person->orders_count }}</strong> order(s).</p>
                        <p class="small text-muted">Select another sales person to receive all customers and orders before delete.</p>
                        <label for="reassign_to_{{ $person->id }}" class="form-label">Reassign to</label>
                        <select name="reassign_to" id="reassign_to_{{ $person->id }}" class="form-select" required>
                            <option value="">Select sales person…</option>
                            @foreach($salesPersons->where('id', '!=', $person->id) as $other)
                                <option value="{{ $other->id }}">{{ $other->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete & reassign</button>
                    </div>
                </form>
@endcanRoute
            </div>
        </div>
    </div>
    @endif
@endforeach

@foreach($salesPersons as $person)
<div class="modal fade" id="assignRouteModal{{ $person->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @canRoute('sales-persons.assign-route')
<form method="POST" action="{{ route('sales-persons.assign-route', $person->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Assign Route — {{ $person->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Sales Route</label>
                    <select name="sales_route_id" class="form-select">
                        <option value="">— No route —</option>
                        @foreach($salesRoutes as $route)
                            <option value="{{ $route->id }}" @selected((string) $person->sales_route_id === (string) $route->id)>
                                {{ $route->name }}@if($route->code) ({{ $route->code }})@endif
                            </option>
                        @endforeach
                    </select>
                    <p class="small text-muted mt-2 mb-0">Sales persons on the same route can see each other's customers. Sales stay with the order owner.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Route</button>
                </div>
            </form>
@endcanRoute

        </div>
    </div>
</div>
@endforeach
@endsection
