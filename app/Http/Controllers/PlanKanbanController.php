<?php

namespace App\Http\Controllers;

use App\Models\AiModel;
use App\Models\AiDailyPlan;
use App\Models\Position;
use App\Models\Trade;
use App\Models\ModelLog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use Carbon\Carbon;

class PlanKanbanController extends Controller
{
    /**
     * Show the plan Kanban view for a given model + date.
     */
    public function index(string $slug, Request $request)
    {
        $models = AiModel::orderByDesc('return_pct')->get();

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
            if(Carbon::parse($plan->trade_date)->lt(Carbon::now('Europe/Copenhagen')->subHours(24)))
                continue; 

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
            'models'    => $models,
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

    public function exportCompletedTrades(string $slug, string $trade_id, Request $request)
    {
        $fileName = 'completed_trade.txt'; // fixed name

        $disk = Storage::disk('local');

        // Delete if exists
        if ($disk->exists($fileName)) {
            $disk->delete($fileName);
        }

        $model = AiModel::where('slug', $slug)->firstOrFail();            

        $trade = Trade::where('ai_model_id', $model->id)
                    ->where('id', $trade_id)
                    ->whereNotNull('closed_at')
                    ->orderByDesc('closed_at')                   
                    ->firstOrFail();
       
        $aiDailyPlan = AiDailyPlan::where('ai_model_id', $model->id)      
                        ->where('trade_date', $trade->date)
                        ->firstOrFail();

        $modellog = ModelLog::where('ai_model_id', $model->id)
                        ->where('action', 'CLOSE')
                        ->whereJsonContains('payload->orders', [
                            'symbol' => $trade->ticker,
                            'side'   => 'SELL'
                        ])
                        ->latest()
                        ->first();        
        
        $content = "=== Completed Trade Export ===\n";
        //$content .= "Exported by: " . auth()->user()->name . "\n";
        $content .= "Export Date: " . now() . "\n\n";

        // Small recheck / summary
        //$content .= "Total Trades: " . $trades->count() . "\n";
        //$content .= "NET PNL: " . $trades->sum('net_pnl') . "\n\n";

        // Detailed log
        //foreach ($trades as $trade) {
            $content .= "Trade ID: {$trade->id}\n";
            $content .= "Symbol: {$trade->ticker} - {$trade->side}\n";
            $content .= "Qty: {$trade->qty}\n";
            $content .= "Entry Price: \${$trade->entry_price}\n";
            $content .= "Exit Price: \${$trade->exit_price}\n";
            $content .= "Net PNL: \${$trade->net_pnl}\n";
            $content .= "Completed At: {$trade->closed_at}\n";
            $content .= "Exit Reason: {$trade->exit_reason_text}\n";

            // if ($modellog && isset($modellog->payload['raw'])) {
            //     $content .= "\n=== Log ===\n";
            //     $content .= json_encode($modellog->payload['raw'], JSON_PRETTY_PRINT);
            //     $content .= "\n";
            // }

            if ($aiDailyPlan && isset($aiDailyPlan->locked_loop_prompt)) {
                $content .= "\n=== Loop Prompt ===\n";
                $content .= json_encode($aiDailyPlan->locked_loop_prompt, JSON_PRETTY_PRINT);
                $content .= "\n";
            }
            
            $content .= "\n"; // single clean blank line between trades
        //}

        // Footer
        $content .= "\n=== End of Export ===\n";

        // Save to storage        
        $disk->put($fileName, $content);
      
       return $disk->download($fileName);
    }

}
