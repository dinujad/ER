<?php

namespace App\Http\Controllers;

use App\Models\ManufacturingJob;
use App\Services\WashingStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class WashingStockController extends Controller
{
    public function __construct(protected WashingStockService $stocks) {}

    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        $jobs = ManufacturingJob::query()
            ->with(['product', 'washingStock', 'washingTransfers', 'hourlyProductions'])
            ->where(function ($query) {
                $query->whereHas('washingStock')
                    ->orWhereHas('washingTransfers')
                    ->orWhereHas('hourlyProductions', fn ($h) => $h->whereNull('employee_id')->where('quantity', '>', 0));
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('job_number', 'like', '%'.$q.'%')
                        ->orWhere('style_ref', 'like', '%'.$q.'%')
                        ->orWhereHas('product', fn ($p) => $p->where('name', 'like', '%'.$q.'%'));
                });
            })
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('erp.manufacturing.washing_stock.index', compact('jobs', 'q'));
    }

    public function show(ManufacturingJob $job)
    {
        $job->load(['product', 'washingStock', 'washingTransfers', 'hourlyProductions', 'washingStockMovements.user']);

        $produced = $job->totalLineHourlyQuantity();
        $received = $this->stocks->received($job);
        $sent = $job->totalWashingSentQuantity();
        $onHand = $this->stocks->onHand($job);
        $availableDeposit = $this->stocks->availableToDeposit($job);

        return view('erp.manufacturing.washing_stock.show', compact(
            'job',
            'produced',
            'received',
            'sent',
            'onHand',
            'availableDeposit'
        ));
    }

    public function deposit(Request $request, ManufacturingJob $job): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
        ]);

        try {
            $this->stocks->deposit(
                $job,
                (int) $data['quantity'],
                auth()->id(),
                $data['note'] ?? 'From line production'
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $message = $data['quantity'].' pcs added to washing stock for '.$job->job_number.'.';

        if ($request->routeIs('supervisor.washing-stock.deposit')) {
            return redirect()->route('supervisor.dashboard')->with('success', $message);
        }

        return redirect()
            ->route('manufacturing.washing-stock.show', $job)
            ->with('success', $message);
    }
}
