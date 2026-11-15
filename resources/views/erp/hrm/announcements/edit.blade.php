@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">Edit Announcement</h4>
        <a href="{{ route('announcements.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    <div class="card">
        <div class="card-body">
            @canRoute('announcements.update')
<form action="{{ route('announcements.update', $announcement) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $announcement->title) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea name="body" class="form-control" rows="4">{{ old('body', $announcement->body) }}</textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $announcement->start_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $announcement->end_date?->format('Y-m-d')) }}">
                    </div>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="is_published" value="1" class="form-check-input" id="pub" {{ old('is_published', $announcement->is_published) ? 'checked' : '' }}>
                        <label class="form-check-label" for="pub">Published</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
            </form>
@endcanRoute
        </div>
    </div>
</div>
@endsection
