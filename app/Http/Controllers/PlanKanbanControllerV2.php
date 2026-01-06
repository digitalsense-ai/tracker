<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AiModel;
use App\Models\AiDailyPlan;
use App\Models\Position;
use App\Models\Trade;

class PlanKanbanControllerV2 extends Controller
{
    public function index(string $slug, Request $request)
    {
        $model = AiModel::where('slug', $slug)->firstOrFail();

        // Optional date filter. If not provided, use latest plan for model.
        $date = $request->query('date');

        $query = AiDailyPlan::where('ai_model_id', $model->id)->orderByDesc('trade_date');
        if ($date) $query->where('trade_date', $date);

        $planRow = $query->first();
        $plan = $planRow ? ($planRow->plan_json ?? []) : [];
        if (!is_array($plan)) $plan = [];

        $openPositions = Position::where('ai_model_id', $model->id)
            ->where('status', 'open')
            ->orderByDesc('opened_at')
            ->get();

        $closedTrades = Trade::where('ai_model_id', $model->id)
            ->whereNotNull('closed_at')
            ->orderByDesc('closed_at')
            ->limit(50)
            ->get();

        // Fallback lookup by symbol if plan_item_id is missing.
        $posBySymbol = [];
        foreach ($openPositions as $p) {
            $sym = strtoupper($p->ticker ?? '');
            if ($sym) $posBySymbol[$sym] = $p;
        }

        $laneIdeaPool = [];
        $laneApproved = [];
        $laneStale = [];
        $laneActivated = [];

        $marketData = app(\App\Services\MarketData::class);

        foreach ($plan as $item) {
            if (!is_array($item)) continue;

            $status = $item['status'] ?? null;
            $sym = strtoupper($item['symbol'] ?? '');

            // Backward compatibility: infer from v1
            if (!$status) {
                $status = !empty($item['approved']) ? 'approved' : 'idea_pool';
            }

            if ($status === 'idea_pool') {
                $laneIdeaPool[] = $item;
                continue;
            }
            if ($status === 'approved') {
                $laneApproved[] = $item;
                continue;
            }
            if ($status === 'stale') {
                $laneStale[] = $item;
                continue;
            }
            if ($status === 'activated') {
                $pos = null;

                if (!empty($item['plan_item_id'])) {
                    $pos = $openPositions->firstWhere('plan_item_id', $item['plan_item_id']);
                }
                if (!$pos && $sym && isset($posBySymbol[$sym])) {
                    $pos = $posBySymbol[$sym];
                }

                $last = null;
                if ($sym) {
                    try { $last = (float) $marketData->getPrice($sym); } catch (\Throwable $e) {}
                }

                $laneActivated[] = [
                    'plan' => $item,
                    'position' => $pos,
                    'last' => $last,
                ];
                continue;
            }

            // Unknown status -> idea_pool
            $laneIdeaPool[] = $item;
        }

        $laneClosed = $closedTrades;

        return view('models.kanban_v2', compact(
            'model',
            'planRow',
            'laneIdeaPool',
            'laneApproved',
            'laneActivated',
            'laneClosed',
            'laneStale',
            'openPositions'
        ));
    }
}
