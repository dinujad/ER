<?php

namespace App\Http\Controllers\Essentials;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Memo;
use Illuminate\Support\Facades\Auth;

class MemoController extends Controller
{
    public function index()
    {
        $memos = Memo::where('user_id', Auth::id())->latest()->get();
        return view('erp.essentials.memos.index', compact('memos'));
    }

    public function store(Request $request)
    {
        $request->validate(['title' => 'required']);
        Memo::create([
            'title' => $request->title,
            'description' => $request->description,
            'user_id' => Auth::id()
        ]);
        return back()->with('success', 'Memo added');
    }

    public function destroy(Memo $memo)
    {
        if ($memo->user_id == Auth::id()) {
            $memo->delete();
        }
        return back()->with('success', 'Memo deleted');
    }
}
