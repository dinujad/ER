<?php

namespace App\Http\Controllers\Essentials;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index()
    {
        $documents = Document::latest()->get();
        return view('erp.essentials.documents.index', compact('documents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'file' => 'required|file|max:10240' // 10MB
        ]);

        $path = $request->file('file')->store('essentials/docs', 'public');

        Document::create([
            'name' => $request->name,
            'path' => $path,
            'type' => $request->file('file')->getClientOriginalExtension(),
            'uploaded_by' => Auth::id()
        ]);

        return back()->with('success', 'Document uploaded');
    }

    public function destroy(Document $document)
    {
        // Simple permission check
        if ($document->uploaded_by == Auth::id()) {
            Storage::disk('public')->delete($document->path);
            $document->delete();
        }
        return back()->with('success', 'Document deleted');
    }
}
