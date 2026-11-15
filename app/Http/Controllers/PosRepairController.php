<?php

namespace App\Http\Controllers;

use App\Models\Repair;
use Illuminate\Http\Request;

class PosRepairController extends Controller
{
    public function index(Request $request)
    {
        $repairs = Repair::with('customer')->orderByDesc('created_at')->paginate(20);
        if ($request->wantsJson()) {
            return response()->json(['repairs' => $repairs->items(), 'total' => $repairs->total()]);
        }
        return view('erp.pos.repairs', compact('repairs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'item' => 'required|string|max:255',
            'issue' => 'nullable|string',
            'estimate' => 'nullable|numeric|min:0',
        ]);
        $ticketNo = 'R' . str_pad((string) (Repair::max('id') + 1), 6, '0', STR_PAD_LEFT);
        $repair = Repair::create([
            'ticket_no' => $ticketNo,
            'customer_id' => $request->customer_id,
            'item' => $request->item,
            'issue' => $request->issue,
            'estimate' => $request->estimate,
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Repair ticket created.',
            'repair' => ['id' => $repair->id, 'ticket_no' => $repair->ticket_no],
        ]);
    }
}
