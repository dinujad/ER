@extends('erp.layout')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0 text-gray-800">Wholesale Customer Profile</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('erp.customers.edit', $customer) }}" class="btn btn-warning btn-sm">Edit</a>
            @if($customer->orders()->doesntExist() && $customer->saleReturns()->doesntExist())
                @canRoute('erp.customers.destroy')
<form method="POST" action="{{ route('erp.customers.destroy', $customer) }}"
                      onsubmit="return confirm('Delete customer &quot;{{ addslashes($customer->display_name) }}&quot;? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">Delete customer</button>
                </form>
@endcanRoute
            @endif
            <a href="{{ route('erp.customers.index', ['tab' => 'wholesale']) }}" class="btn btn-secondary btn-sm">Back to list</a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">{{ $customer->display_name }}</h6>
            <span class="badge bg-info">Wholesale</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><strong>Business name:</strong> {{ $customer->business_name ?? '—' }}</div>
                <div class="col-md-6"><strong>Contact name:</strong> {{ $customer->name ?? '—' }}</div>
                <div class="col-md-6"><strong>Phone:</strong> {{ $customer->phone ?? '—' }}</div>
                <div class="col-md-6"><strong>Email:</strong> {{ $customer->email ?? '—' }}</div>
                <div class="col-md-6"><strong>Address:</strong> {{ $customer->address ?? '—' }}</div>
                <div class="col-md-6"><strong>Added by:</strong> {{ $customer->addedBy->name ?? 'Admin / System' }}</div>
                <div class="col-md-6"><strong>BR number:</strong> {{ $customer->br_number ?? '—' }}</div>
                <div class="col-md-6"><strong>TIN number:</strong> {{ $customer->tin ?? '—' }}</div>
                <div class="col-md-12"><strong>Business address:</strong> {{ $customer->business_address ?? '—' }}</div>
                <div class="col-md-12">
                    <strong>BR document:</strong>
                    @if($customer->br_document_path)
                        <a href="{{ asset('storage/' . $customer->br_document_path) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary ms-2">View document</a>
                    @else
                        <span class="text-muted">Not uploaded</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('erp.customers._profile_detail', ['profileContext' => 'erp'])
</div>
@endsection
