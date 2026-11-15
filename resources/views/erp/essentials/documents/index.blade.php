@extends('erp.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Essentials</h1>
</div>

@include('erp.essentials.nav')

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Document Repository</h6>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
            <i class="bi bi-upload"></i> Upload
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Uploaded On</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documents as $doc)
                    <tr>
                        <td>
                            <a href="{{ Storage::url($doc->path) }}" target="_blank" class="fw-bold text-dark text-decoration-none">
                                <i class="bi bi-file-earmark-text me-2"></i> {{ $doc->name }}
                            </a>
                        </td>
                        <td>{{ strtoupper($doc->type) }}</td>
                        <td>{{ $doc->created_at->format('Y-m-d') }}</td>
                        <td>
                             @canRoute('essentials.documents.destroy')
<form action="{{ route('essentials.documents.destroy', $doc->id) }}" method="POST" onsubmit="return confirm('Delete this document?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                            </form>
@endcanRoute
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="uploadDocModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
             <form action="{{ route('essentials.documents.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Upload Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Document Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>File</label>
                        <input type="file" name="file" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
             </form>
        </div>
    </div>
</div>
@endsection
