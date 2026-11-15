@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-lg-8">
            <h4 class="mb-1">Production Sections</h4>
            <p class="text-sm text-muted mb-0">
                Manage factory departments. Click a section to assign employees and supervisor.
                Sewing → open lines → assign team per line.
            </p>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
            @if($sections->isEmpty())
                <form action="{{ route('sections.seed-defaults') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-magic me-1"></i> Create Default Sections
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if($sections->isEmpty())
        <div class="card border-warning border-2 shadow-sm mb-4">
            <div class="card-body text-center py-5">
                <i class="bi bi-diagram-3 text-warning" style="font-size: 3rem;"></i>
                <h5 class="mt-3">No production sections yet</h5>
                <p class="text-muted mb-4">
                    Database reset කළාම sections හිස් වෙනවා. පහළින් <strong>Create Default Sections</strong> click කරන්න
                    (Cutting, Washing, Finishing, Sewing Lines 01–03).
                </p>
                <form action="{{ route('sections.seed-defaults') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning fw-bold px-4">
                        <i class="bi bi-magic me-1"></i> Create Default Sections
                    </button>
                </form>
            </div>
        </div>
    @else
        <div class="row">
            @foreach($sections as $section)
            <div class="col-md-4 mb-4">
                <div class="card h-100 card-hover-effect shadow-sm border-0"
                     onclick="window.location='{{ route('sections.show', $section->id) }}'"
                     style="cursor: pointer;">
                    <div class="card-body text-center p-4">
                        <div class="icon icon-shape icon-lg bg-gradient-primary shadow text-center border-radius-lg mb-3 mx-auto">
                            <i class="bi bi-diagram-3 text-white opacity-10" style="font-size: 1.5rem;"></i>
                        </div>
                        <h5 class="font-weight-bolder">{{ $section->name }}</h5>
                        @if($section->slug === 'sewing')
                            <p class="text-xs text-muted mb-2">Contains sub-lines — click to manage lines & teams</p>
                            <span class="badge bg-info">{{ $section->children()->count() }} Lines</span>
                        @else
                            <span class="badge bg-secondary">{{ $section->employees_count }} Employees</span>
                            <p class="text-xs text-muted mt-2 mb-0">Click to assign team</p>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif

    <div class="row mt-2">
        <div class="col-md-5">
            <div class="card shadow-sm border-0">
                <div class="card-header pb-0">
                    <h6 class="mb-0">Add New Department</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('sections.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="type" value="department">
                        <div class="mb-3">
                            <label class="form-label">Section name</label>
                            <input type="text" name="name" class="form-control border px-2"
                                   placeholder="e.g. QC Section" required value="{{ old('name') }}">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-plus-lg me-1"></i> Add Section
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
