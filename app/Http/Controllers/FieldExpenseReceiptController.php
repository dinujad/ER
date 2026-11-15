<?php

namespace App\Http\Controllers;

use App\Models\SalesPersonExpense;
use App\Models\User;
use App\Models\VehicleDailyRunItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FieldExpenseReceiptController extends Controller
{
    public function salesPerson(Request $request, SalesPersonExpense $expense): StreamedResponse
    {
        $this->authorizeSalesPersonExpense($request, $expense);

        return $this->streamSlip($expense->slip_path);
    }

    public function driver(Request $request, VehicleDailyRunItem $item): StreamedResponse
    {
        $this->authorizeDriverExpense($request, $item);

        return $this->streamSlip($item->slip_path);
    }

    protected function authorizeSalesPersonExpense(Request $request, SalesPersonExpense $expense): void
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        if ($this->userCanViewAllFieldExpenses($user)) {
            return;
        }

        if ($user->role === 'sales_person' && (int) $expense->user_id === (int) $user->id) {
            return;
        }

        if ($user->can_access('sales_persons.view')) {
            return;
        }

        abort(403);
    }

    protected function authorizeDriverExpense(Request $request, VehicleDailyRunItem $item): void
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        if ($this->userCanViewAllFieldExpenses($user)) {
            return;
        }

        $item->loadMissing('run');
        $driver = $user->driver;
        if ($user->role === 'driver' && $driver && (int) $item->run?->driver_id === (int) $driver->id) {
            return;
        }

        abort(403);
    }

    protected function userCanViewAllFieldExpenses(User $user): bool
    {
        if ($user->isSystemAdmin()) {
            return true;
        }

        return $user->can_access('expenses.view');
    }

    protected function streamSlip(?string $path): StreamedResponse
    {
        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'Receipt not found.');
        }

        return Storage::disk('public')->response($path, basename($path), [
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }
}
