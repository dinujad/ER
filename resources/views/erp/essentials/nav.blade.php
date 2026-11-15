<ul class="nav nav-pills mb-4 gap-2 p-2 bg-white rounded shadow-sm border">
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center {{ request()->routeIs('essentials.todo.*') ? 'active bg-primary text-white shadow-sm fw-bold border-bottom border-3 border-light' : 'text-secondary bg-light' }}" 
           href="{{ route('essentials.todo.index') }}"
           style="transition: all 0.2s ease;">
            <i class="bi bi-check2-circle me-2 fs-5"></i>
            <span>To Do</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center {{ request()->routeIs('essentials.documents.*') ? 'active bg-primary text-white shadow-sm fw-bold border-bottom border-3 border-light' : 'text-secondary bg-light' }}" 
           href="{{ route('essentials.documents.index') }}"
           style="transition: all 0.2s ease;">
            <i class="bi bi-file-earmark-text me-2 fs-5"></i>
            <span>Documents</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center {{ request()->routeIs('essentials.memos.*') ? 'active bg-primary text-white shadow-sm fw-bold border-bottom border-3 border-light' : 'text-secondary bg-light' }}" 
           href="{{ route('essentials.memos.index') }}"
           style="transition: all 0.2s ease;">
            <i class="bi bi-sticky me-2 fs-5"></i>
            <span>Memos</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center {{ request()->routeIs('essentials.reminders.*') ? 'active bg-primary text-white shadow-sm fw-bold border-bottom border-3 border-light' : 'text-secondary bg-light' }}" 
           href="{{ route('essentials.reminders.index') }}"
           style="transition: all 0.2s ease;">
            <i class="bi bi-bell me-2 fs-5"></i>
            <span>Reminders</span>
        </a>
    </li>
</ul>
