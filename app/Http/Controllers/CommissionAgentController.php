<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CommissionAgent;

class CommissionAgentController extends Controller
{
    public function index()
    {
        $agents = CommissionAgent::all();
        return view('erp.agents.index', compact('agents'));
    }

    public function create()
    {
        return view('erp.agents.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'agent_name' => 'required|string',
            'contact' => 'required|string',
            'commission_type' => 'required|in:percentage,fixed',
            'commission_rate' => 'required|numeric|min:0',
        ]);

        CommissionAgent::create($request->all());

        return redirect()->route('agents.index')->with('success', 'Commission Agent added successfully.');
    }
}
