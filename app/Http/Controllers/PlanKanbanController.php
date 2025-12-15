<?php

namespace App\Http\Controllers;

use App\Models\AiModel;
use App\Models\AiDailyPlan;
use App\Models\Position;
use App\Models\Trade;
use Illuminate\Http\Request;

class PlanKanbanController extends Controller
{
    /**
     * Show the plan Kanban view for a given model + date.
     */
    public function index(string $slug, Request $request)
    {
        $model = AiModel::where('slug', $slug)->firstOrFail();

        //$date = $request->query('date', now()->toDateString());
        $date = $request->query('date');

        $query = AiDailyPlan::where('ai_model_id', $model->id);
        
        if($date)
            $query->where('trade_date', $date);            

        $plans = $query->get(); 

        // $plan = AiDailyPlan::where('ai_model_id', $model->id)
        //     ->where('trade_date', $date)
        //     ->first();

        // $strategies = $plan ? ($plan->plan_json ?? []) : [];

        $ideaPool = [];
        $approved = [];

        foreach ($plans as $key => $plan) {
            $strategies = $plan ? ($plan->plan_json ?? []) : [];

            foreach ($strategies as $idx => $s) {
                if (!is_array($s)) {
                    continue;
                }

                // Normalise an id field so we can reference this strategy later.
                if (!isset($s['id'])) {
                    $s['id'] = $idx;
                }

                if (!empty($s['approved'])) {
                    $approved[] = $s;
                } else {
                    $ideaPool[] = $s;
                }
            }
        }//for loop plans

        $openPositions = Position::where('ai_model_id', $model->id)
            ->where('status', 'open')
            ->orderByDesc('opened_at')
            ->get();

        $completedTrades = Trade::where('ai_model_id', $model->id)
            ->whereNotNull('closed_at')
            ->orderByDesc('closed_at')
            ->limit(25)
            ->get();

        // Build a set of symbols that are currently live (open positions)
        $liveSymbols = $openPositions
           ->pluck('ticker')
           ->filter()
           ->map(fn($s) => strtoupper($s))
           ->unique()
           ->all();
        // Filter approved strategies so any strategy whose symbol is already live
        // does NOT stay in the "Approved for Today" lane.
        $approvedFiltered = [];
        foreach ($approved as $s) {
           $sym = strtoupper($s['symbol'] ?? '');
           if ($sym && in_array($sym, $liveSymbols, true)) {
               // This strategy is already live via an open position -> skip from lane 2
               continue;
           }
           $approvedFiltered[] = $s;
        }
        // Replace the original approved list
        $approved = $approvedFiltered;

        return view('models.kanban', [
            'model'           => $model,
            'date'            => $date,            
            'plan'            => ($date) ? $plans->first() : $plan,
            'ideaPool'        => $ideaPool,
            'approved'        => $approved,
            'openPositions'   => $openPositions,
            'completedTrades' => $completedTrades,
        ]);
    }

    /**
     * Update approvals for today's strategies.
     */
    public function update(string $slug, Request $request)
    {
        $model = AiModel::where('slug', $slug)->firstOrFail();
        $date  = $request->input('date', now()->toDateString());

        $plan = AiDailyPlan::where('ai_model_id', $model->id)
            ->where('trade_date', $date)
            ->first();

        if (! $plan) {
            return redirect()
                ->route('models.kanban', ['slug' => $slug, 'date' => $date])
                ->with('error', 'No plan exists for this date.');
        }

        $strategies = $plan->plan_json ?? [];
        $approvedIds = $request->input('approved', []);
        if (!is_array($approvedIds)) {
            $approvedIds = [];
        }

        $approvedIds = array_map('strval', $approvedIds);

        foreach ($strategies as $idx => &$s) {
            if (!is_array($s)) {
                continue;
            }
            if (!isset($s['id'])) {
                $s['id'] = $idx;
            }

            $id = (string) $s['id'];
            $s['approved'] = in_array($id, $approvedIds, true);
        }
        unset($s);

        $plan->plan_json = $strategies;
        $plan->save();

        return redirect()
            ->route('models.kanban', ['slug' => $slug, 'date' => $date])
            ->with('status', 'Plan approvals updated.');
    }
}
