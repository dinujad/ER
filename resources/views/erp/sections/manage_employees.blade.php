@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
                    <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="{{ route('sections.index') }}">Sections</a></li>
                    @if($section->parent)
                        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="{{ route('sections.show', $section->parent_id) }}">{{ $section->parent->name }}</a></li>
                    @endif
                    <li class="breadcrumb-item text-sm text-dark active" aria-current="page">{{ $section->name }}</li>
                </ol>
            </nav>
            <h4 class="font-weight-bolder mb-0">Assign Team: {{ $section->name }}</h4>
        </div>
    </div>

    @php
        // Identify Supervisor
        $supervisorId = $section->supervisor_id;
        $supervisorEmp = $employees->where('id', $supervisorId)->first();

        // Assigned members EXCLUDING supervisor (to avoid double listing if they are technically 'assigned' too)
        $assignedMembers = $employees->where('production_section_id', $section->id)->where('id', '!=', $supervisorId);
        
        $available = $employees->where('production_section_id', null)->where('id', '!=', $supervisorId);
    @endphp

    <div class="card">
        <div class="card-body">
            <form id="assignForm" action="{{ route('sections.assign.store', $section->id) }}" method="POST">
                @csrf
                
                <div class="d-flex justify-content-end mb-3">
                     <a href="{{ route('sections.index') }}" class="btn btn-secondary me-2">Cancel</a>
                     <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i> Save Changes
                     </button>
                </div>
                
                <h6 class="mb-3">Overview</h6>
                <div class="row align-items-end mb-4">
                    @if(isset($stages) && $stages->isNotEmpty())
                    <div class="col-md-4">
                        <label class="form-label">Production stage (for ingredient usage)</label>
                        <select name="production_stage_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach($stages as $st)
                                <option value="{{ $st->id }}" {{ $section->production_stage_id == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Links this section to recipe steps. Supervisor can then record actual ingredient usage.</small>
                    </div>
                    @endif
                    <div class="col-md-4">
                        <label class="form-label">Assign Supervisor</label>
                        <select name="supervisor_id" id="supervisorSelect" class="form-select">
                            <option value="">-- Select Supervisor --</option>
                            @foreach($supervisors as $supervisor)
                                <option value="{{ $supervisor->id }}" 
                                    data-name="{{ $supervisor->name }}"
                                    data-nic="{{ $supervisor->nic }}"
                                    data-role="{{ ucfirst(str_replace('_', ' ', $supervisor->role)) }}"
                                    {{ $section->supervisor_id == $supervisor->id ? 'selected' : '' }}>
                                    {{ $supervisor->name }} ({{ $supervisor->nic }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Team Members</h6>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                        <i class="bi bi-plus-lg me-1"></i> Add Member
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table align-items-center mb-0 table-hover" id="assignedTable">
                        <thead class="bg-light">
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Employee Name</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Role</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">NIC</th>
                                <th class="text-secondary opacity-7"></th>
                            </tr>
                        </thead>
                        <tbody id="assignedTableBody">
                            {{-- 1. Render Supervisor First --}}
                            @if($supervisorEmp)
                                <tr id="row-{{ $supervisorEmp->id }}" class="bg-light-warning">
                                    <td>
                                        <div class="d-flex px-2 py-1">
                                            <div class="d-flex flex-column justify-content-center">
                                                <h6 class="mb-0 text-sm text-primary">
                                                    <i class="bi bi-star-fill text-warning me-1"></i> {{ $supervisorEmp->name }}
                                                </h6>
                                                 <input type="hidden" name="employee_ids[]" value="{{ $supervisorEmp->id }}">
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0 text-primary">Supervisor (Head)</p>
                                    </td>
                                    <td>
                                        <p class="text-xs text-secondary mb-0">{{ $supervisorEmp->nic }}</p>
                                    </td>
                                    <td class="align-middle text-end">
                                        {{-- No Delete Button for Supervisor --}}
                                        <span class="badge bg-secondary">Supervisor</span>
                                    </td>
                                </tr>
                            @endif

                            {{-- 2. Render Other Members --}}
                            @forelse($assignedMembers as $emp)
                                <tr id="row-{{ $emp->id }}">
                                    <td>
                                        <div class="d-flex px-2 py-1">
                                            <div class="d-flex flex-column justify-content-center">
                                                <h6 class="mb-0 text-sm">{{ $emp->name }}</h6>
                                                 <input type="hidden" name="employee_ids[]" value="{{ $emp->id }}">
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0">{{ ucfirst(str_replace('_', ' ', $emp->role)) }}</p>
                                    </td>
                                    <td>
                                        <p class="text-xs text-secondary mb-0">{{ $emp->nic }}</p>
                                    </td>
                                    <td class="align-middle text-end">
                                        <button type="button" class="btn btn-link text-danger text-gradient p-0 mb-0" onclick="removeEmployee({{ $emp->id }})">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                @if(!$supervisorEmp)
                                    <tr id="noMembersRow">
                                        <td colspan="4" class="text-center text-sm py-4 text-muted">No team members assigned yet.</td>
                                    </tr>
                                @endif
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </form>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Employees to Add</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchEmployee" class="form-control" placeholder="Search by name, NIC, or role...">
                </div>
                
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50px;">Select</th>
                                <th>Name</th>
                                <th>Role</th>
                                <th>NIC</th>
                            </tr>
                        </thead>
                        <tbody id="availableTableBody">
                            @foreach($available as $emp)
                            <tr class="available-row" data-search="{{ strtolower($emp->name . ' ' . $emp->role . ' ' . $emp->nic) }}" id="modal-row-{{ $emp->id }}">
                                <td class="text-center">
                                    <div class="form-check d-flex justify-content-center">
                                        <input class="form-check-input available-check" type="checkbox" value="{{ $emp->id }}" 
                                            data-name="{{ $emp->name }}" 
                                            data-role="{{ ucfirst(str_replace('_', ' ', $emp->role)) }}" 
                                            data-nic="{{ $emp->nic }}">
                                    </div>
                                </td>
                                <td><span class="text-sm font-weight-bold">{{ $emp->name }}</span></td>
                                <td><span class="text-xs">{{ ucfirst($emp->role) }}</span></td>
                                <td><span class="text-xs text-secondary">{{ $emp->nic }}</span></td>
                            </tr>
                            @endforeach
                             {{-- Render Assigned members (hidden) --}}
                             @foreach($assignedMembers as $emp)
                             <tr class="available-row d-none" data-search="{{ strtolower($emp->name . ' ' . $emp->role . ' ' . $emp->nic) }}" id="modal-row-{{ $emp->id }}">
                                 <td class="text-center">
                                     <div class="form-check d-flex justify-content-center">
                                        <input class="form-check-input available-check" type="checkbox" value="{{ $emp->id }}" 
                                            data-name="{{ $emp->name }}" 
                                            data-role="{{ ucfirst(str_replace('_', ' ', $emp->role)) }}" 
                                            data-nic="{{ $emp->nic }}">
                                     </div>
                                 </td>
                                 <td><span class="text-sm font-weight-bold">{{ $emp->name }}</span></td>
                                 <td><span class="text-xs">{{ ucfirst($emp->role) }}</span></td>
                                 <td><span class="text-xs text-secondary">{{ $emp->nic }}</span></td>
                             </tr>
                             @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="addSelectedEmployees()">Add Selected</button>
            </div>
        </div>
    </div>
</div>

<script>
    // --- New Logic: Supervisor Dropdown Sync ---
    document.getElementById('supervisorSelect').addEventListener('change', function() {
        const selectedId = this.value;
        // Remove existing supervisor row if any
        const existingSupRow = document.querySelector('.bg-light-warning');
        if(existingSupRow) {
            existingSupRow.remove();
        }

        if(selectedId) {
             const option = this.options[this.selectedIndex];
             const name = option.getAttribute('data-name');
             const nic = option.getAttribute('data-nic');
             
             // Insert at Top
             const tbody = document.getElementById('assignedTableBody');
             const newRow = `
                <tr id="row-${selectedId}" class="bg-light-warning">
                    <td>
                        <div class="d-flex px-2 py-1">
                            <div class="d-flex flex-column justify-content-center">
                                <h6 class="mb-0 text-sm text-primary">
                                    <i class="bi bi-star-fill text-warning me-1"></i> ${name}
                                </h6>
                                <input type="hidden" name="employee_ids[]" value="${selectedId}">
                            </div>
                        </div>
                    </td>
                    <td>
                        <p class="text-xs font-weight-bold mb-0 text-primary">Supervisor (Head)</p>
                    </td>
                    <td>
                        <p class="text-xs text-secondary mb-0">${nic}</p>
                    </td>
                    <td class="align-middle text-end">
                        <span class="badge bg-secondary">Supervisor</span>
                    </td>
                </tr>
             `;
             tbody.insertAdjacentHTML('afterbegin', newRow);
             
             // Also ensure this person is REMOVED/HIDDEN from main table and modal if they were there
             const modalRow = document.getElementById('modal-row-' + selectedId);
             if(modalRow) { modalRow.classList.add('d-none'); } // Hide in modal

             // If they were in the main list as a regular member, remove that row
             // But caution: ID might clash if we don't distinguish ID. 
             // Currently IDs are unique "row-ID". So if we just added "row-ID" as sup, 
             // duplicate ID might technically exist if we don't delete old one first.
             // Let's Clean up any existing normal row with same ID first.
             const oldNormalRow = document.getElementById('row-' + selectedId);
             if(oldNormalRow && !oldNormalRow.classList.contains('bg-light-warning')) {
                 oldNormalRow.remove();
             }
        }
        
    });

    // Search Filtering
    document.getElementById('searchEmployee').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('.available-row:not(.d-none-assigned)'); // Don't filter rows that are hidden because they are assigned
        
        rows.forEach(row => {
            if (row.classList.contains('d-none')) return; // Skip currently hidden/assigned rows
            
            let text = row.getAttribute('data-search');
            if (text.includes(filter)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    function addSelectedEmployees() {
        const checkboxes = document.querySelectorAll('.available-check:checked');
        const assignedTableBody = document.getElementById('assignedTableBody');
        const noMembersRow = document.getElementById('noMembersRow');

        if(checkboxes.length > 0 && noMembersRow) {
            noMembersRow.style.display = 'none';
        }

        checkboxes.forEach(chk => {
            const id = chk.value;
            const name = chk.getAttribute('data-name');
            const role = chk.getAttribute('data-role');
            const nic = chk.getAttribute('data-nic');

            // 1. Add to Main Table
            const newRow = `
                <tr id="row-${id}">
                    <td>
                        <div class="d-flex px-2 py-1">
                            <div class="d-flex flex-column justify-content-center">
                                <h6 class="mb-0 text-sm">${name}</h6>
                                <input type="hidden" name="employee_ids[]" value="${id}">
                            </div>
                        </div>
                    </td>
                    <td><p class="text-xs font-weight-bold mb-0">${role}</p></td>
                    <td><p class="text-xs text-secondary mb-0">${nic}</p></td>
                    <td class="align-middle text-end">
                        <button type="button" class="btn btn-link text-danger text-gradient p-0 mb-0" onclick="removeEmployee(${id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            assignedTableBody.insertAdjacentHTML('beforeend', newRow);

            // 2. Hide from Modal
            const modalRow = document.getElementById('modal-row-' + id);
            if(modalRow) {
                modalRow.classList.add('d-none'); // Hide visually
                modalRow.classList.add('d-none-assigned'); // Mark as assigned so search doesn't mess with it
                chk.checked = false; // Uncheck
            }
        });

        // Close Modal
        var myModalEl = document.getElementById('addEmployeeModal');
        var modal = bootstrap.Modal.getInstance(myModalEl);
        modal.hide();
    }

    function removeEmployee(id) {
        // 1. Remove from Main Table
        const row = document.getElementById('row-' + id);
        if(row) row.remove();

        // 2. Show in Modal List
        const modalRow = document.getElementById('modal-row-' + id);
        if(modalRow) {
            modalRow.classList.remove('d-none');
            modalRow.classList.remove('d-none-assigned');
             // Re-apply search filter in case there is text in search
             const searchVal = document.getElementById('searchEmployee').value.toLowerCase();
             if(modalRow.getAttribute('data-search').includes(searchVal)) {
                 modalRow.style.display = '';
             } else {
                 if(searchVal !== '') modalRow.style.display = 'none';
             }
        }

        // Check if empty
        const assignedTableBody = document.getElementById('assignedTableBody');
        if (assignedTableBody.children.length === 0 || (assignedTableBody.children.length === 1 && assignedTableBody.children[0].id === 'noMembersRow')) {
             const noRow = document.getElementById('noMembersRow');
             if(noRow) noRow.style.display = 'table-row';
        }
    }
</script>
@endsection
