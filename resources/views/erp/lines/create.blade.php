@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header pb-0 text-left">
                    <h4 class="font-weight-bolder">Add New Production Line</h4>
                    <p class="mb-0 text-sm">Create a new line to organize production.</p>
                </div>
                <div class="card-body">
                    <form role="form" method="POST" action="{{ route('lines.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label>Line Name</label>
                            <input type="text" class="form-control" name="name" placeholder="e.g. Line 03" required>
                        </div>
                        <div class="mb-3">
                            <label>Description</label>
                            <textarea class="form-control" name="description" placeholder="Optional description"></textarea>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="activeCheck">
                            <label class="form-check-label" for="activeCheck">Active</label>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-lg btn-primary btn-lg w-100 mt-4 mb-0">Create Line</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
