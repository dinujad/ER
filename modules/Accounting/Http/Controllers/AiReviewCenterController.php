<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\Accounting\Ai\AiReviewCenterService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

class AiReviewCenterController extends Controller
{
    use RequiresAccountingPermission;

    public function index(AiReviewCenterService $service)
    {
        $pending = Schema::hasTable('acct_ai_review_tasks')
            ? $service->pending($this->sessionBusinessId())
            : collect();

        return view('accounting::phase5.review.index', [
            'pageTitle' => 'AI Review Center',
            'pageSub' => 'Human authorization required',
            'tasks' => $pending,
        ]);
    }

    public function show(int $id)
    {
        $task = DB::table('acct_ai_review_tasks')
            ->where('id', $id)
            ->where('business_id', $this->sessionBusinessId())
            ->first();
        if (! $task) {
            abort(404);
        }
        $decisions = DB::table('acct_ai_review_decisions')
            ->where('review_task_id', $id)
            ->orderByDesc('id')
            ->get();

        return view('accounting::phase5.review.show', [
            'pageTitle' => 'Review Task #'.$id,
            'pageSub' => $task->title,
            'task' => $task,
            'decisions' => $decisions,
            'payload' => json_decode($task->suggestion_payload ?? '{}', true) ?: [],
        ]);
    }

    public function decide(int $id, Request $request, AiReviewCenterService $service)
    {
        $this->requireManage();
        $request->validate([
            'decision' => 'required|in:accepted,rejected,clarification',
            'note' => 'nullable|string|max:2000',
        ]);

        $edited = $request->input('edited');
        if (is_string($edited)) {
            $edited = json_decode($edited, true);
        }

        $service->decide(
            $this->sessionBusinessId(),
            $id,
            $request->input('decision'),
            (int) Auth::id(),
            is_array($edited) ? $edited : null,
            $request->input('note')
        );

        return redirect('accounting/phase5/review')->with('status', 'Decision recorded. No journals were posted by AI review.');
    }
}
