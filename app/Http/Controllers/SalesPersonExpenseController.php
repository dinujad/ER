<?php

namespace App\Http\Controllers;

use App\Models\SalesPersonExpense;
use App\Services\SalesRouteRunService;
use App\Support\SalesPersonSafeRedirect;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SalesPersonExpenseController extends Controller
{
    public function __construct(protected SalesRouteRunService $routeRuns) {}
    public function index(Request $request)
    {
        $userId = auth()->id();
        $month = $request->input('month', now()->format('Y-m'));

        try {
            $start = Carbon::parse($month . '-01')->startOfMonth();
        } catch (\Throwable) {
            $month = now()->format('Y-m');
            $start = now()->startOfMonth();
        }
        $end = $start->copy()->endOfMonth();

        $expenses = SalesPersonExpense::query()
            ->where('user_id', $userId)
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $monthTotal = (float) SalesPersonExpense::query()
            ->where('user_id', $userId)
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        $monthOptions = collect(range(0, 11))->map(function ($i) {
            $d = now()->subMonths($i);

            return ['value' => $d->format('Y-m'), 'label' => $d->format('F Y')];
        });

        return view('sales_person.expenses.index', compact(
            'expenses',
            'month',
            'monthTotal',
            'monthOptions',
            'start'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $slipUploadError = $this->slipUploadErrorMessage($request);
        if ($slipUploadError) {
            return back()->withInput()->withErrors(['slip' => $slipUploadError]);
        }

        $validated = $request->validate([
            'expense_date' => 'required|date',
            'type' => 'required|string|in:' . implode(',', array_keys(SalesPersonExpense::typeOptions())),
            'description' => 'required|string|max:150',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
            'slip' => 'nullable|file|max:10240',
            'redirect_to' => 'nullable|string|max:500',
        ], [
            'slip.max' => 'Slip file එක 10MB ට වඩා විශාලයි. කුඩා photo/PDF එකක් upload කරන්න.',
        ]);

        if ($request->hasFile('slip') && !$this->isAllowedSlipFile($request->file('slip'))) {
            return back()->withInput()->withErrors([
                'slip' => 'Slip JPG, PNG, WEBP, HEIC, හෝ PDF file එකක් විය යුතුයි.',
            ]);
        }

        try {
            $expense = DB::transaction(function () use ($request, $validated) {
                $expense = SalesPersonExpense::create([
                    'user_id' => auth()->id(),
                    'sales_route_run_id' => $this->routeRuns->activeRunFor(auth()->user())?->id,
                    'expense_date' => $validated['expense_date'],
                    'type' => $validated['type'],
                    'description' => $validated['description'],
                    'amount' => $validated['amount'],
                    'notes' => $validated['notes'] ?? null,
                ]);

                if ($request->hasFile('slip')) {
                    Storage::disk('public')->makeDirectory('sales-person-expenses');
                    $path = $request->file('slip')->store('sales-person-expenses', 'public');
                    if (!$path) {
                        throw new \RuntimeException('Slip path empty after store.');
                    }
                    $expense->slip_path = $path;
                    $expense->save();
                }

                return $expense;
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'slip' => 'Slip save කරන්න බැරි වුණා. File එක 10MB ට අඩු JPG/PNG/PDF දාලා නැවත try කරන්න. තවමත් issue එකක් නම් admin ට fix-permissions script run කරන්න කියන්න.',
            ]);
        }

        return $this->redirectAfterExpense($request)
            ->with('success', 'Expense saved (Rs ' . number_format((float) $expense->amount, 2) . ').');
    }

    public function destroy(Request $request, SalesPersonExpense $expense): RedirectResponse
    {
        if ($expense->user_id !== auth()->id()) {
            abort(403);
        }

        if ($expense->slip_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($expense->slip_path);
        }

        $expense->delete();

        return $this->redirectAfterExpense($request)->with('success', 'Expense removed.');
    }

    protected function redirectAfterExpense(Request $request): RedirectResponse
    {
        $safe = SalesPersonSafeRedirect::path(
            is_string($request->input('redirect_to')) ? $request->input('redirect_to') : null,
            $request
        );

        if ($safe !== null) {
            return redirect()->to($safe);
        }

        return redirect()->route('sales-person.expenses.index');
    }

    protected function slipUploadErrorMessage(Request $request): ?string
    {
        if (!isset($_FILES['slip'])) {
            return null;
        }

        $upload = $_FILES['slip'];
        $error = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($error === UPLOAD_ERR_OK) {
            return null;
        }

        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Slip file එක වැඩි වැඩියි. 10MB ට අඩු JPG, PNG, හෝ PDF එකක් upload කරන්න.',
            UPLOAD_ERR_PARTIAL => 'Slip upload අඩක් වැටුණා. Internet connection බලලා නැවත try කරන්න.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'Server එකේ file upload setup issue එකක් තියෙනවා. Admin ට දන්වන්න.',
            UPLOAD_ERR_EXTENSION => 'Server එක මෙම file type එක block කරලා තියෙනවා.',
            default => 'Slip upload වැරදුණා. වෙන JPG/PNG/PDF photo එකක් try කරන්න.',
        };
    }

    protected function isAllowedSlipFile(?\Illuminate\Http\UploadedFile $file): bool
    {
        if (!$file || !$file->isValid()) {
            return false;
        }

        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'];
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (in_array($extension, $allowedExtensions, true)) {
            return true;
        }

        $mime = strtolower((string) $file->getMimeType());
        $allowedMimes = [
            'application/pdf',
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/webp',
            'image/heic',
            'image/heif',
            'image/heic-sequence',
            'application/octet-stream',
        ];

        return in_array($mime, $allowedMimes, true);
    }
}
