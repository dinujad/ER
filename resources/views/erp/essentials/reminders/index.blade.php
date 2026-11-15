@extends('erp.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Essentials</h1>
</div>

@include('erp.essentials.nav')

<div class="row">
    <!-- Reminder List -->
    <div class="col-md-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Upcoming Reminders</h6>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <div class="list-group">
                    @forelse($reminders as $reminder)
                        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">{{ $reminder->title }}</h5>
                                <small class="text-muted">
                                    <i class="bi bi-calendar-event me-1"></i> 
                                    {{ \Carbon\Carbon::parse($reminder->date)->format('M d, Y h:i A') }}
                                </small>
                            </div>
                            @canRoute('essentials.reminders.destroy')
<form action="{{ route('essentials.reminders.destroy', $reminder->id) }}" method="POST" onsubmit="return confirm('Delete this reminder?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger rounded-circle" type="submit">
                                    <i class="bi bi-x"></i>
                                </button>
                            </form>
@endcanRoute
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-bell-slash fs-1 d-block mb-3"></i>
                            No upcoming reminders.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Create Reminder Form -->
    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-header py-3 bg-primary text-white">
                <h6 class="m-0 font-weight-bold"><i class="bi bi-plus-circle me-2"></i>Set Reminder</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('essentials.reminders.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Reminder Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Call Supplier" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date & Time</label>
                        <input type="datetime-local" name="date" class="form-control" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-bell me-2"></i> Set Reminder
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
