<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\Accounting\Ai\AiAssistantService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

class AiAssistantController extends Controller
{
    use RequiresAccountingPermission;

    public function index()
    {
        $businessId = $this->sessionBusinessId();
        $user = Auth::user();
        $conversations = Schema::hasTable('acct_ai_conversations')
            ? DB::table('acct_ai_conversations')
                ->where('business_id', $businessId)
                ->where('user_id', $user->id)
                ->orderByDesc('last_message_at')
                ->limit(20)
                ->get()
            : collect();

        return view('accounting::phase5.assistant.index', [
            'pageTitle' => 'AI Accounting Assistant',
            'pageSub' => 'Grounded financial Q&A · LKR',
            'conversations' => $conversations,
            'suggested' => [
                'What was our profit last month?',
                'පසුගිය මාසයේ expenses කොච්චරද?',
                'Compare this month\'s revenue with last month.',
                'Show overdue customer invoices.',
                'Which suppliers have the highest outstanding balances?',
                'Show our current cash position.',
                'How much depreciation was recorded this month?',
                'What is our loan outstanding balance?',
                'Summarize our financial performance.',
            ],
        ]);
    }

    public function ask(Request $request, AiAssistantService $assistant)
    {
        $request->validate([
            'question' => 'required|string|max:4000',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'locale' => 'nullable|in:en,si',
            'conversation_id' => 'nullable|integer',
        ]);

        $user = Auth::user();
        if (! $user || (! $user->isSystemAdmin() && ! $user->can_access('accounting.view') && ! $user->can_access('accounting.ai.assistant'))) {
            abort(403);
        }

        try {
            $result = $assistant->ask($this->sessionBusinessId(), $user, $request->input('question'), [
                'from' => $request->input('from'),
                'to' => $request->input('to'),
                'locale' => $request->input('locale'),
                'conversation_id' => $request->integer('conversation_id') ?: null,
            ]);
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return back()->withErrors(['question' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return view('accounting::phase5.assistant.index', [
            'pageTitle' => 'AI Accounting Assistant',
            'pageSub' => 'Grounded financial Q&A · LKR',
            'result' => $result,
            'conversations' => collect(),
            'suggested' => [],
        ]);
    }

    public function show(int $id)
    {
        $businessId = $this->sessionBusinessId();
        $user = Auth::user();
        $conversation = DB::table('acct_ai_conversations')
            ->where('id', $id)
            ->where('business_id', $businessId)
            ->where('user_id', $user->id)
            ->first();
        if (! $conversation) {
            abort(404);
        }
        $messages = DB::table('acct_ai_messages')
            ->where('conversation_id', $id)
            ->orderBy('id')
            ->get();

        return view('accounting::phase5.assistant.show', [
            'pageTitle' => 'Conversation',
            'pageSub' => $conversation->title,
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }
}
