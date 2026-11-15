@extends('erp.layout')

@section('title', 'Barcode Settings')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1">Barcode Settings</h1>
        <p class="text-muted small mb-0">Templates for Print Labels. Barcodes print as <strong>CODE128 (C128)</strong> so POS scanners can read them.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('barcode.settings.test') }}" target="_blank" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-upc-scan me-1"></i> Test C128 print
        </a>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSettingModal">
            <i class="bi bi-plus"></i> Add New Template
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Sticker (W × H)</th>
                    <th>Sheet / Roll</th>
                    <th>Labels / row</th>
                    <th>Type</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($settings as $s)
                <tr>
                    <td>
                        {{ $s->name }}
                        @if($s->is_default)
                            <span class="badge bg-success">Default</span>
                        @endif
                    </td>
                    <td>{{ number_format((float) $s->sticker_width, 2) }}" × {{ number_format((float) $s->sticker_height, 2) }}"</td>
                    <td>
                        {{ $s->is_continuous ? 'Continuous roll' : 'Sheet' }}
                        ({{ number_format((float) $s->paper_width, 2) }}" wide)
                    </td>
                    <td>{{ $s->stickersPerRow() }}</td>
                    <td><span class="badge bg-dark">C128</span></td>
                    <td class="text-nowrap text-end">
                        @canRoute('barcode.settings.update')
                        <button
                            type="button"
                            class="btn btn-sm btn-info edit-setting-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#editSettingModal"
                            data-action="{{ route('barcode.settings.update', $s) }}"
                            data-name="{{ $s->name }}"
                            data-sticker-width="{{ $s->sticker_width }}"
                            data-sticker-height="{{ $s->sticker_height }}"
                            data-paper-width="{{ $s->paper_width }}"
                            data-stickers-per-row="{{ $s->stickersPerRow() }}"
                            data-continuous="{{ $s->is_continuous ? '1' : '0' }}"
                        >Edit</button>
                        @endcanRoute
                        @canRoute('barcode.settings.destroy')
<form action="{{ route('barcode.settings.destroy', $s) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this template?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
@endcanRoute
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-muted text-center py-4">No templates yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addSettingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('barcode.store_setting') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">New barcode template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('erp.barcode._setting_fields', ['setting' => null])
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save template</button>
                </div>
            </form>
        </div>
    </div>
</div>

@canRoute('barcode.settings.update')
<div class="modal fade" id="editSettingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="edit-setting-form" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit barcode template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('erp.barcode._setting_fields', ['setting' => null, 'idPrefix' => 'edit_'])
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update template</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcanRoute
@endsection

@push('scripts')
<script>
document.querySelectorAll('.edit-setting-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const form = document.getElementById('edit-setting-form');
        form.action = btn.getAttribute('data-action');
        form.querySelector('[name="name"]').value = btn.getAttribute('data-name') || '';
        form.querySelector('[name="sticker_width"]').value = btn.getAttribute('data-sticker-width') || '';
        form.querySelector('[name="sticker_height"]').value = btn.getAttribute('data-sticker-height') || '';
        form.querySelector('[name="paper_width"]').value = btn.getAttribute('data-paper-width') || '';
        form.querySelector('[name="stickers_per_row"]').value = btn.getAttribute('data-stickers-per-row') || '1';
        form.querySelector('[name="is_continuous"]').checked = btn.getAttribute('data-continuous') === '1';
    });
});
</script>
@endpush
