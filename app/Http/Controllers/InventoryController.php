<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialIssue;
use App\Models\MaterialStockMovement;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturnItem;
use App\Services\MaterialStockService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(
        protected MaterialStockService $stockService
    ) {}
    public function index(Request $request)
    {
        $type = $request->get('type', 'all');
        $query = Material::query()->orderBy('name');

        if (in_array($type, ['fabric', 'accessory'], true)) {
            $query->where('type', $type);
        }

        $materials = $query->get();

        return view('erp.inventory.index', compact('materials', 'type'));
    }

    public function create()
    {
        return view('erp.inventory.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'type' => 'required|in:fabric,accessory',
            'quantity' => 'required|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'unit' => 'required',
        ]);

        $qty = (float) $request->quantity;
        $data = $request->only(['name', 'type', 'unit', 'unit_price']);
        $data['quantity'] = 0;
        $material = Material::create($data);

        if ($qty > 0) {
            $this->stockService->applyMovement(
                $material,
                $qty,
                MaterialStockMovement::TYPE_OPENING,
                null,
                null,
                null,
                'Opening stock on create',
                auth()->id(),
                false
            );
        }

        return redirect()->route('inventory.index')->with('success', 'Material added successfully.');
    }

    public function edit($id)
    {
        $material = Material::findOrFail($id);
        return view('erp.inventory.edit', compact('material'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'type' => 'required|in:fabric,accessory',
            'quantity' => 'required|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'unit' => 'required',
        ]);

        $material = Material::findOrFail($id);
        $oldQty = (float) $material->quantity;
        $newQty = (float) $request->quantity;
        $diff = round($newQty - $oldQty, 2);

        $data = $request->only(['name', 'type', 'unit', 'unit_price']);
        $data['quantity'] = $oldQty;
        $material->update($data);

        if (abs($diff) >= 0.0001) {
            $this->stockService->applyMovement(
                $material->fresh(),
                $diff,
                MaterialStockMovement::TYPE_ADJUSTMENT,
                null,
                null,
                null,
                'Manual quantity edit',
                auth()->id(),
                true
            );
        }

        return redirect()->route('inventory.index')->with('success', 'Material updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $material = Material::findOrFail($id);
        $redirectType = in_array($request->get('redirect_type'), ['fabric', 'accessory', 'all'], true)
            ? $request->get('redirect_type')
            : 'all';

        $blockReason = $this->materialDeleteBlockReason($material);
        if ($blockReason) {
            return redirect()
                ->route('inventory.index', $redirectType === 'all' ? [] : ['type' => $redirectType])
                ->with('inventory_delete_error', $blockReason);
        }

        try {
            $material->delete();
        } catch (QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1451) {
                return redirect()
                    ->route('inventory.index', $redirectType === 'all' ? [] : ['type' => $redirectType])
                    ->with(
                        'inventory_delete_error',
                        '"' . $material->name . '" delete කරන්න බෑ — මෙම item එක වෙන records වලට link වී ඇත.'
                    );
            }

            throw $e;
        }

        return redirect()
            ->route('inventory.index', $redirectType === 'all' ? [] : ['type' => $redirectType])
            ->with('success', 'Material deleted successfully.');
    }

    protected function materialDeleteBlockReason(Material $material): ?string
    {
        $name = $material->name;

        if (PurchaseItem::where('material_id', $material->id)->exists()) {
            return '"' . $name . '" delete කරන්න බෑ — මෙම item එක purchase records වලට use වෙලා තියෙනවා. පළමුව ඒ purchases වලින් item එක remove කරන්න හෝ purchase delete කරන්න.';
        }

        if (PurchaseReturnItem::where('material_id', $material->id)->exists()) {
            return '"' . $name . '" delete කරන්න බෑ — මෙම item එක purchase return records වලට link වී ඇත.';
        }

        return null;
    }

    public function issueForm()
    {
        $materials = Material::where('quantity', '>', 0)->get();
        return view('erp.inventory.issue', compact('materials'));
    }

    public function issueMaterial(Request $request)
    {
        $request->validate([
            'material_id' => 'required|exists:materials,id',
            'quantity_issued' => 'required|numeric|min:0.01',
            'issued_to' => 'required',
        ]);

        $material = Material::findOrFail($request->material_id);
        $qty = (float) $request->quantity_issued;

        $issue = MaterialIssue::create([
            'material_id' => $material->id,
            'quantity_issued' => $qty,
            'issued_to' => $request->issued_to,
        ]);

        try {
            $this->stockService->applyMovement(
                $material,
                -$qty,
                MaterialStockMovement::TYPE_ISSUE,
                MaterialIssue::class,
                $issue->id,
                'Issue #' . $issue->id,
                'Issued to: ' . $request->issued_to,
                auth()->id(),
                true
            );
        } catch (\InvalidArgumentException $e) {
            $issue->delete();

            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('inventory.index')->with('success', 'Material issued successfully.');
    }

    public function history(Material $material)
    {
        $movements = $material->stockMovements()
            ->with('creator')
            ->paginate(30);

        $returnIds = $movements->getCollection()
            ->where('movement_type', MaterialStockMovement::TYPE_PURCHASE_RETURN)
            ->pluck('reference_id')
            ->filter();

        $returnPurchaseRoutes = \App\Models\PurchaseReturn::query()
            ->with('purchase:id')
            ->whereIn('id', $returnIds)
            ->get()
            ->filter(fn ($r) => $r->purchase)
            ->mapWithKeys(fn ($r) => [$r->id => route('purchases.show', $r->purchase_id)]);

        return view('erp.inventory.history', compact('material', 'movements', 'returnPurchaseRoutes'));
    }

    /**
     * Quick-add material from purchase screen (returns JSON for dropdown).
     */
    public function quickStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:fabric,accessory',
            'unit' => 'required|string|max:50',
            'unit_price' => 'nullable|numeric|min:0',
        ]);

        $material = Material::create([
            'name' => $request->name,
            'type' => $request->type,
            'unit' => $request->unit,
            'quantity' => 0,
            'unit_price' => $request->input('unit_price', 0),
        ]);

        return response()->json([
            'success' => true,
            'material' => [
                'id' => $material->id,
                'name' => $material->name,
                'unit' => $material->unit ?? '',
                'unit_price' => (float) ($material->unit_price ?? 0),
            ],
        ]);
    }
}
