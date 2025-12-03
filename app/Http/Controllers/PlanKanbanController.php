<?php

namespace App\Http\Controllers;

use App\Models\AiModel;
use App\Models\AiDailyPlan;
use App\Models\Position;
use App\Models\Trade;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PlanKanbanController extends Controller
{
    /**
     * Show a kanban-style view of today's plan, approvals, and trades.
     */
    public function index(string $slug, Request $request)
    {
        $model = AiModel::where('slug', $slug)->firstOrFail();
        $date  = $request->query('date', Carbon::today()->toDateString());

        $plan = AiDailyPlan::where('ai_model_id', $model->id)
            ->where('trade_date', $date)
            ->first();

        $strategies = $plan ? ($plan->plan_json ?? []) : [];

        $ideaPool  = [];
        $approved  = [];

        foreach ($strategies as $s) {
            if (!empty($s['approved'])) {
                $approved[] = $s;
            } else {
                $ideaPool[] = $s;
            }
        }

        $openPositions = Position::where('ai_model_id', $model->id)
            ->where('status', 'open')
            ->orderBy('opened_at', 'desc')
            ->get();

        $recentClosedTrades = Trade::where('ai_model_id', $model->id)
            ->whereNotNull('closed_at')   // ✅ use closed_at, not status
            ->orderBy('closed_at', 'desc')
            ->limit(25)
            ->get();

        return view('models.kanban', [
            'model'              => $model,
            'date'               => $date,
            'plan'               => $plan,
            'ideaPool'           => $ideaPool,
            'approvedStrategies' => $approved,
            'openPositions'      => $openPositions,
            'recentClosedTrades' => $recentClosedTrades,
        ]);
    }

    /**
     * Update approvals for strategies in today's plan.
     */
    public function update(string $slug, Request $request)
    {
        $model = AiModel::where('slug', $slug)->firstOrFail();
        $date  = $request->input('date', Carbon::today()->toDateString());

        $plan = AiDailyPlan::where('ai_model_id', $model->id)
            ->where('trade_date', $date)
            ->first();

        if (!$plan) {
            return redirect()
                ->route('models.kanban', [$model->slug])
                ->with('error', 'No plan exists for this date.');
        }

        $strategies = $plan->plan_json ?? [];
        $approvedIds = $request->input('approved', []); // indexes or ids
        $approvedIds = array_map('strval', (array) $approvedIds);

        foreach ($strategies as $idx => &$s) {
            $id = isset($s['id']) ? (string) $s['id'] : (string) $idx;
            $s['approved'] = in_array($id, $approvedIds, true);
        }
        unset($s);

        $plan->plan_json = $strategies;
        $plan->save();

        return redirect()
            ->route('models.kanban', [$model->slug, 'date' => $date])
            ->with('ok', 'Updated approvals.');
    }
}
