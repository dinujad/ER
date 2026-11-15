@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
                    <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="{{ route('sections.index') }}">Sections</a></li>
                    <li class="breadcrumb-item text-sm text-dark active" aria-current="page">{{ $section->name }}</li>
                </ol>
            </nav>
            <h4 class="font-weight-bolder mb-0">Manage {{ $section->name }}</h4>
        </div>
    </div>

    <div class="row">
        <!-- List of Sub-Lines -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header pb-0">
                    <h6>Production Lines / Sub-Sections</h6>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Name</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Assigned Staff</th>
                                    <th class="text-secondary opacity-7"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($subSections as $sub)
                                <tr class="cursor-pointer" onclick="window.location='{{ route('sections.show', $sub->id) }}'">
                                    <td class="px-4">
                                        <h6 class="mb-0 text-sm">{{ $sub->name }}</h6>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="badge bg-info">{{ $sub->employees_count }} Staff</span>
                                    </td>
                                    <td class="align-middle text-end px-4">
                                        <a href="{{ route('sections.show', $sub->id) }}" class="btn btn-sm btn-primary">
                                            Manage Team <i class="bi bi-arrow-right ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add New Line Form -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header pb-0">
                    <h6>Add New Line</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('sections.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="parent_id" value="{{ $section->id }}">
                        <input type="hidden" name="type" value="line">
                        
                        <div class="mb-3">
                            <label>Line Name</label>
                            <input type="text" class="form-control" name="name" placeholder="e.g. Line 04" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Create Line</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
