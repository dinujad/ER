@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <h4 class="mb-4 fw-bold">Announcements</h4>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <p><a href="{{ route('announcements.create') }}" class="btn btn-primary btn-sm">Add Announcement</a></p>
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-bordered mb-0">
                <thead>
                    <tr><th>Title</th><th>Period</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($announcements as $a)
                        <tr>
                            <td>{{ $a->title }}</td>
                            <td>{{ $a->start_date ? $a->start_date->format('d M Y') : '—' }} - {{ $a->end_date ? $a->end_date->format('d M Y') : '—' }}</td>
                            <td>{{ $a->is_published ? 'Published' : 'Draft' }}</td>
                            <td>
                                @canRoute('announcements.edit')
<a href="{{ route('announcements.edit', $a) }}" class="btn btn-sm btn-outline-primary">Edit</a>
@endcanRoute
                                @canRoute('announcements.destroy')
<form action="{{ route('announcements.destroy', $a) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this announcement?');">@csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
@endcanRoute
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No announcements.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($announcements->hasPages())
            <div class="card-footer">{{ $announcements->links() }}</div>
        @endif
    </div>
</div>
@endsection
