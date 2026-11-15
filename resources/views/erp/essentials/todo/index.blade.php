@extends('erp.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Essentials</h1>
</div>

<!-- Tabs -->
@include('erp.essentials.nav')

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">To Do List</h6>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTaskModal">
            <i class="bi bi-plus"></i> Add Task
        </button>
    </div>
    <div class="card-body">
        
        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-md-3">
                <select id="filter_status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            <div class="col-md-3">
                <select id="filter_priority" class="form-select">
                    <option value="">All Priorities</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-secondary w-100" onclick="reloadTable()">Apply Filters</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered" id="todo_table" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Added On</th>
                        <th>Task ID</th>
                        <th>Task</th>
                        <th>Status</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Est. Hours</th>
                        <th>Assigned By</th>
                        <th>Assigned To</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addTaskForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add New Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Task Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="row mb-3">
                         <div class="col-6">
                            <label>Status</label>
                            <select name="status" class="form-select">
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label>Priority</label>
                            <select name="priority" class="form-select">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                             <label>Start Date</label>
                             <input type="date" name="start_date" class="form-control">
                        </div>
                         <div class="col-6">
                             <label>End Date</label>
                             <input type="date" name="end_date" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Assigned To</label>
                        <select name="assigned_to" class="form-select">
                            <option value="">-- Select User --</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="submitTask()">Save Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<!-- DataTables CDN -->
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
    let table;
    $(document).ready(function() {
        table = $('#todo_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('essentials.todo.index') }}",
                data: function (d) {
                    d.status = $('#filter_status').val();
                    d.priority = $('#filter_priority').val();
                }
            },
            columns: [
                {data: 'created_at', name: 'created_at'},
                {data: 'task_id', name: 'task_id'},
                {data: 'title', name: 'title'},
                {data: 'status', name: 'status'},
                {data: 'start_date', name: 'start_date'},
                {data: 'end_date', name: 'end_date'},
                {data: 'estimated_hours', name: 'estimated_hours'},
                {data: 'assigned_by', name: 'assigned_by'},
                {data: 'assigned_to', name: 'assigned_to'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });
    });

    function reloadTable() {
        table.draw();
    }

    function submitTask() {
        let formData = new FormData(document.getElementById('addTaskForm'));
        
        fetch("{{ route('essentials.todo.store') }}", {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // Close modal using bootstrap API
                let modalEl = document.getElementById('addTaskModal');
                let modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();
                
                document.getElementById('addTaskForm').reset();
                reloadTable();
            } else {
                alert('Error submitting task');
            }
        });
    }
</script>
@endsection
