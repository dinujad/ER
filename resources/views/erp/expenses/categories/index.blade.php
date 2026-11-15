@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-0 fw-bold">Expense Categories</h4>
            <p class="text-muted small mb-0">Cost Sheet topics — ERP → Expenses වලින් enter කරන categories මෙතැනින් map වෙනවා</p>
        </div>
        <div class="d-flex gap-2">
            @if(auth()->user()?->can_access('manufacturing.costs'))
            <a href="{{ route('reports.show', 'cost-report') }}" class="btn btn-outline-success btn-sm">Cost Report</a>
            @endif
            <a href="{{ route('expense-categories.create') }}" class="btn btn-primary btn-sm">Add Category</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @foreach($categoriesByTopic as $topicKey => $group)
        @php $topicMeta = $topics[$topicKey] ?? null; @endphp
        <div class="card mb-3">
            <div class="card-header">
                <strong>{{ $group['label'] }}</strong>
                <span class="text-muted ms-1">({{ $group['label_si'] }})</span>
                @if($topicMeta)
                    <div class="small text-muted mt-1">{{ $topicMeta['description'] }}</div>
                @endif
            </div>
            <div class="card-body p-0">
                @if(!empty($topicMeta['subtopics']))
                <div class="px-3 py-2 bg-light border-bottom small">
                    <strong class="text-muted">Include:</strong>
                    @foreach($topicMeta['subtopics'] as $sub)
                        <span class="badge bg-white border text-dark me-1 mb-1">{{ $sub['name'] }}</span>
                    @endforeach
                </div>
                @endif
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Category name</th>
                                <th>Code</th>
                                <th>Description</th>
                                <th class="text-center">Expenses</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($group['categories'] as $cat)
                                <tr>
                                    <td class="fw-semibold">{{ $cat->name }}</td>
                                    <td><code>{{ $cat->code ?? '—' }}</code></td>
                                    <td class="small text-muted">{{ $cat->description ?? '—' }}</td>
                                    <td class="text-center">{{ $cat->expenses_count ?? 0 }}</td>
                                    <td class="text-nowrap">
                                        @canRoute('expense-categories.edit')
<a href="{{ route('expense-categories.edit', $cat) }}" class="btn btn-sm btn-outline-primary">Edit</a>
@endcanRoute
                                        @canRoute('expense-categories.destroy')
<form action="{{ route('expense-categories.destroy', $cat) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this category?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
@endcanRoute
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted text-center py-3">No categories in this topic yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
