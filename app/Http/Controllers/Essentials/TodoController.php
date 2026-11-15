<?php

namespace App\Http\Controllers\Essentials;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Todo;
use App\Models\User; // Assuming User model exists
use Illuminate\Support\Str;
use Carbon\Carbon;

class TodoController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getDataTable($request);
        }

        $users = User::all(); // For filters
        return view('erp.essentials.todo.index', compact('users'));
    }

    private function getDataTable(Request $request)
    {
        try {
            $query = Todo::query()->with(['assignedBy', 'assignedTo']);

        // Filtering
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }
        if ($request->has('priority') && $request->priority != '') {
            $query->where('priority', $request->priority);
        }
        
        // Search
        if ($request->has('search') && !empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('task_id', 'like', "%{$search}%");
            });
        }

        // Sorting
        if ($request->has('order')) {
            $orderColumnIndex = $request->order[0]['column'];
            $orderDir = $request->order[0]['dir'];
            $columns = ['created_at', 'task_id', 'title', 'status', 'start_date', 'end_date', 'estimated_hours', 'assigned_to'];
            
            if (isset($columns[$orderColumnIndex])) {
                 $query->orderBy($columns[$orderColumnIndex], $orderDir);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $totalRecords = Todo::count();
        $filteredRecords = $query->count();
        
        // Pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 10;
        $data = $query->skip($start)->take($length)->get();

        // Formatting
        $formattedData = $data->map(function ($todo) {
            return [
                'created_at' => $todo->created_at->format('Y-m-d'),
                'task_id' => $todo->task_id,
                'title' => $todo->title . '<br><small class="text-muted">'.$todo->priority.'</small>',
                'status' => ucfirst($todo->status),
                'start_date' => $todo->start_date,
                'end_date' => $todo->end_date,
                'estimated_hours' => $todo->estimated_hours,
                'assigned_by' => optional($todo->assignedBy)->name ?? 'N/A',
                'assigned_to' => optional($todo->assignedTo)->name ?? 'Unassigned',
                'action' => '<button class="btn btn-sm btn-primary">Edit</button>'
            ];
        });

            return response()->json([
                'draw' => intval($request->draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $formattedData
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Todo DataTable Error: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error($e->getTraceAsString());
            return response()->json(['error' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'status' => 'required',
            'priority' => 'required'
        ]);

        $todo = Todo::create([
            'business_id' => 1,
            'task_id' => 'TD-' . time(), // Simple unique ID
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->status,
            'priority' => $request->priority,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'estimated_hours' => $request->estimated_hours,
            'assigned_by' => auth()->id() ?? 1, // Fallback if no auth
            'assigned_to' => $request->assigned_to,
            'created_by' => auth()->id() ?? 1
        ]);
        
        return response()->json(['success' => true, 'message' => 'Task Created']);
    }
}
