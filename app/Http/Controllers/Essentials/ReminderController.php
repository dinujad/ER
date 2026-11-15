<?php

namespace App\Http\Controllers\Essentials;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reminder; // Need to create this model if not exists, but I probably created table only.
use Illuminate\Support\Facades\Auth;

class ReminderController extends Controller
{
    public function index()
    {
        // Fetch reminders
        // Use DB table 'reminders' created in 2026_02_06_163000
        $reminders = \Illuminate\Support\Facades\DB::table('reminders')
                        ->where('user_id', Auth::id() ?? 1)
                        ->orderBy('date', 'asc')
                        ->get();

        return view('erp.essentials.reminders.index', compact('reminders'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'date' => 'required|date'
        ]);

        \Illuminate\Support\Facades\DB::table('reminders')->insert([
            'title' => $request->title,
            'date' => $request->date,
            'user_id' => Auth::id() ?? 1,
            'is_completed' => false,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return back()->with('success', 'Reminder set successfully');
    }

    public function destroy($id)
    {
        \Illuminate\Support\Facades\DB::table('reminders')->where('id', $id)->delete();
        return back()->with('success', 'Reminder deleted');
    }
}
