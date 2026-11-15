<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with('creator')->latest()->paginate(15);
        return view('erp.hrm.announcements.index', compact('announcements'));
    }

    public function create()
    {
        return view('erp.hrm.announcements.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        Announcement::create([
            'title' => $request->title,
            'body' => $request->body,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_published' => $request->boolean('is_published', true),
            'created_by' => auth()->id(),
        ]);
        return redirect()->route('announcements.index')->with('success', 'Announcement created.');
    }

    public function edit(Announcement $announcement)
    {
        return view('erp.hrm.announcements.edit', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        $announcement->update([
            'title' => $request->title,
            'body' => $request->body,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_published' => $request->boolean('is_published', true),
        ]);
        return redirect()->route('announcements.index')->with('success', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        return redirect()->route('announcements.index')->with('success', 'Announcement deleted.');
    }
}
