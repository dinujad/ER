@extends('erp.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Essentials</h1>
</div>

@include('erp.essentials.nav')

<div class="row">
    <!-- List -->
    <div class="col-md-8">
        <div class="row">
            @foreach($memos as $memo)
            <div class="col-md-6 mb-3">
                <div class="card shadow h-100 border-left-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h5 class="card-title font-weight-bold">{{ $memo->title }}</h5>
                            @canRoute('essentials.memos.destroy')
<form action="{{ route('essentials.memos.destroy', $memo->id) }}" method="POST" onsubmit="return confirm('Delete this memo?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm text-danger"><i class="bi bi-trash"></i></button>
                            </form>
@endcanRoute
                        </div>
                        <p class="card-text text-muted">{{ Str::limit($memo->description, 100) }}</p>
                        <small class="text-secondary">{{ $memo->created_at->diffForHumans() }}</small>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    
    <!-- Create Form -->
    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Add Memo</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('essentials.memos.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Note</label>
                        <textarea name="description" class="form-control" rows="5"></textarea>
                    </div>
                    <button type="submit" class="btn btn-warning w-100">Pin Memo</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
