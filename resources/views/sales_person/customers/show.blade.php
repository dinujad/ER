@extends('sales_person.layout')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0">Customer profile</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('sales-person.customers.edit', $customer) }}" class="btn btn-warning btn-sm">Edit</a>
            <a href="{{ route('sales-person.customers.index') }}" class="btn btn-secondary btn-sm">Back to my customers</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">{{ $customer->display_name }}</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><strong>Phone:</strong> {{ $customer->phone ?? '—' }}</div>
                <div class="col-md-6"><strong>Email:</strong> {{ $customer->email ?? '—' }}</div>
                <div class="col-md-6"><strong>Address:</strong> {{ $customer->address ?? '—' }}</div>
                <div class="col-md-6"><strong>Business:</strong> {{ $customer->business_name ?? '—' }}</div>
                <div class="col-md-6"><strong>BR number:</strong> {{ $customer->br_number ?? '—' }}</div>
                <div class="col-md-6"><strong>TIN number:</strong> {{ $customer->tin ?? '—' }}</div>
                <div class="col-md-12"><strong>Business address:</strong> {{ $customer->business_address ?? '—' }}</div>
            </div>
        </div>
    </div>

    @include('erp.customers._profile_detail', ['profileContext' => 'sales_person'])
</div>
@endsection
