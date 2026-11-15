{{-- @var \App\Models\Vehicle $vehicle --}}
{{-- @var \Illuminate\Support\Collection $drivers --}}
{{-- @var \Illuminate\Support\Collection $assignments --}}
<div class="card shadow mb-4" id="daily-assignments">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-bold">Daily driver assignment</h6>
        <span class="text-muted small">දිනය අනුව driver change කරන්න</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('vehicles.assignments.store', $vehicle) }}" class="row g-3 mb-4 border-bottom pb-4">
            @csrf
            <div class="col-md-4">
                <label class="form-label">Date <span class="text-danger">*</span></label>
                <input type="date" name="assignment_date" class="form-control @error('assignment_date') is-invalid @enderror"
                    value="{{ old('assignment_date', date('Y-m-d')) }}" required>
                @error('assignment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Driver <span class="text-danger">*</span></label>
                <select name="driver_id" class="form-select @error('driver_id') is-invalid @enderror" required>
                    <option value="">— Select driver —</option>
                    @foreach($drivers as $d)
                        <option value="{{ $d->id }}" @selected(old('driver_id') == $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
                @error('driver_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Notes</label>
                <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" placeholder="Optional">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-success"><i class="bi bi-person-check me-1"></i> Assign for this date</button>
            </div>
        </form>

        <h6 class="text-muted small text-uppercase mb-2">Recent & upcoming assignments</h6>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Driver</th>
                        <th>Notes</th>
                        <th>Assigned by</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignments as $a)
                        <tr class="{{ $a->assignment_date->isToday() ? 'table-info' : '' }}">
                            <td>
                                {{ $a->assignment_date->format('d M Y') }}
                                @if($a->assignment_date->isToday())
                                    <span class="badge bg-primary ms-1">Today</span>
                                @endif
                            </td>
                            <td>{{ $a->driver?->name ?? '—' }}</td>
                            <td>{{ $a->notes ?: '—' }}</td>
                            <td class="small text-muted">{{ $a->assignedByUser?->name ?? '—' }}</td>
                            <td class="text-end">
                                @canRoute('vehicles.assignments.destroy')
<form action="{{ route('vehicles.assignments.destroy', [$vehicle, $a]) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('Remove this assignment?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                </form>
@endcanRoute
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">
                                No daily assignments yet. Default driver (if set) will be used until you assign per date.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
