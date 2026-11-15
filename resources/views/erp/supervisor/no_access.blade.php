@extends('erp.layout')

@section('content')
<div class="container py-5 text-center">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <i class="bi bi-exclamation-circle text-warning display-1 mb-3"></i>
                    <h3>Access Denied / Not Assigned</h3>
                    <p class="text-muted">{{ $message }}</p>
                    <p>Please contact HR or Admin to assign you to a specific Production Section.</p>
                    <a href="{{ route('erp.dashboard') }}" class="btn btn-primary mt-3">Go Home</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
