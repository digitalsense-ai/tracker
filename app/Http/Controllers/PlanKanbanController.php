<?php

namespace App\Http\Controllers;

use App\Models\AiModel;
use App\Models\AiDailyPlan;
use App\Models\Position;
use App\Models\Trade;
use App\Models\ModelLog;
use App\Models\SaxoInstrument;

use App\Services\SaxoTokenService;
use App\Services\PaperBroker;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

use Carbon\Carbon;
use Carbon\CarbonInterval;

class PlanKanbanController extends Controller
{
    protected SaxoTokenService $tokenService;

    public function __construct(SaxoTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Show the plan Kanban view for a given model + date.
     */
    public function index(string $slug, Request $request)
    {       
        $broker = app(PaperBroker::class);

        $models = AiModel::orderByDesc('return_pct')->get();

        $model = AiModel::where('slug', $slug)->firstOrFail();

        //$date = $request->query('date', now()->toDateString());
        $date = $request->query('date');

        $query = AiDailyPlan::where('ai_model_id', $model->id);
        
        if($date)
        {
            $query->where('trade_date', $date);            

            //Approve auto
            $plan = $query->first();

            if($plan)
            {
                //Allowed Symbols only with mode = "active_on_open"
                // Open positions
                $open = Position::where('ai_model_id', $model->id)
                            ->where('status', 'open')
                            ->pluck('ticker');                

                $approved = collect($plan['plan_json'] ?? [])
                                ->filter(fn ($s) =>
                                is_array($s) 
                                &&
                                    (
                                        (!empty($s['approved']) && $s['approved']) ||
                                        (($s['status'] ?? null) === 'approved') ||
                                        (!empty($s['keep']))
                                    )
                                && (($s['mode'] ?? null) === 'active_on_open')    
                                )
                                ->pluck('symbol');

                $allowed = $approved
                            ->map(fn ($s) => strtoupper($s))
                            ->diff(
                            $open->map(fn ($s) => strtoupper($s))
                            )
                            ->values()
                            ->all();                          
                //Allowed Symbols only with mode = "active_on_open"

                $strategies = $plan ? ($plan->plan_json ?? []) : [];

                foreach ($strategies as $idx => &$s) {
                    if (!is_array($s)) {
                        continue;
                    }
                    if (!isset($s['id'])) {
                        $s['id'] = $idx;
                    }

                    $id = (string) $s['id'];
                    if(isset($s['approved']))                
                        $s['approved'] = (!$s['approved']) ? true : $s['approved'];
                    else               
                        $s['approved'] = true;

                    //Move to LIVE TRADE (LANE 3) without AI Tick when mode = "active_on_open"
                    // Support either `symbol` or `ticker` from the AI
                    $symbol = strtoupper($s['symbol'] ?? $s['ticker'] ?? '');

                    if (!$symbol || !in_array($symbol, $allowed, true)) {                        
                        continue; // 🔒 HARD STOP — skip execution
                    }

                    // Normalise side into our DB enum: 'long' / 'short'
                    $rawSide = strtolower($s['side'] ?? $s['direction'] ?? 'long');
                    if ($rawSide === 'buy') {
                        $side = 'long';
                    } elseif ($rawSide === 'sell') {
                        $side = 'short';
                    } elseif (in_array($rawSide, ['long','short'], true)) {
                        $side = $rawSide;
                    } else {
                        $side = 'long';
                    }

                    $qty    = (float) ($s['qty'] ?? 1);
                    if (!$symbol || $qty <= 0) {
                        continue;
                    }

                    $broker->openPosition($model, $symbol, $side, $qty);
                    //Move to LIVE TRADE (LANE 3) without AI Tick when mode = "active_on_open"
                }
                unset($s);

                $plan->plan_json = $strategies;
                $plan->save();
            }
        }

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
           

            $saxo_instrument = SaxoInstrument::where('symbol', 'LIKE', $sym . '%')->firstOrFail();
 
           if($saxo_instrument)
           {
            $uic = $saxo_instrument->uic;
            $assetType = $saxo_instrument->asset_type;

            $baseUrl = rtrim(config('services.saxo.base_url'), '/');
       
            $accessToken = app(SaxoTokenService::class)->getToken();

            if (!$baseUrl || !$accessToken) {
                $this->error('Missing Saxo base_url or access_token in config/services.php (services.saxo.*).');
                return self::FAILURE;
            }

            $response = Http::withToken($accessToken)           
                //->get("https://gateway.saxobank.com/sim/openapi/ref/v1/instruments/details/{$uic}/{$assetType}");
                ->get("https://gateway.saxobank.com/sim/openapi/ref/v1/instruments/tradingschedule/{$uic}/{$assetType}");

            if ($response->failed()) {
                throw new \Exception('Failed to fetch instruments: ' . $response->body());
            }

            $data = $response->json();
           
            $marketTiming = $this->getMarketTiming($data);

            //$s['InstrumentDetails'] = $data;
            $s['marketTiming'] = $marketTiming;

            $approvedFiltered[] = $s;
           }
           else
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
        
    function getMarketTiming(array $instrumentDetails): array
    {
        $sessions = collect($instrumentDetails['Sessions']);
        $exchangeOffset = $instrumentDetails['TimeZoneOffset']; // "-05:00:00"

        $nowUtc = now()->utc();
        $nowExchange = $nowUtc->copy()->setTimezone($exchangeOffset);

        // 1️⃣ Find current session
        $currentSession = $sessions->first(function ($session) use ($nowUtc) {
            return $nowUtc->gte(Carbon::parse($session['StartTime']))
                && $nowUtc->lt(Carbon::parse($session['EndTime']));
        });

        $currentState = $currentSession['State'] ?? 'Closed';

        // 2️⃣ If market is OPEN (AutomatedTrading)
        if ($currentState === 'AutomatedTrading') {

            $end = Carbon::parse($currentSession['EndTime']);
            $diff = $nowUtc->diff($end);

            return [
                'is_open' => true,
                'state' => $currentState,
                'message' => 'Market closes in ' . $diff->format('%hh %Im'),
                'exchange_time' => $nowExchange->format('Y-m-d H:i:s'),
            ];
        }

        // 3️⃣ Find NEXT AutomatedTrading session
        $nextOpenSession = $sessions
            ->filter(fn ($s) =>
                $s['State'] === 'AutomatedTrading'
                && Carbon::parse($s['StartTime'])->gt($nowUtc)
            )
            ->sortBy('StartTime')
            ->first();

        if ($nextOpenSession) {

            $openTime = Carbon::parse($nextOpenSession['StartTime']);
            $diff = $nowUtc->diff($openTime);

            return [
                'is_open' => false,
                'state' => $currentState,
                'message' => 'Market opens in ' . $diff->format('%hh %Im'),
                'opens_at_exchange_time' => $openTime
                    ->copy()
                    ->setTimezone($exchangeOffset)
                    ->format('Y-m-d H:i:s'),
                'exchange_time_now' => $nowExchange->format('Y-m-d H:i:s'),
            ];
        }

        return [
            'is_open' => false,
            'state' => 'Closed',
            'message' => 'No upcoming trading session found',
        ];
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

/*
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

       //  // Save to storage        
       //  $disk->put($fileName, $content);
      
       // return $disk->download($fileName);

         return response()->streamDownload(function () use ($content) {
            echo $content;
        }, 'completed_trade_' . now()->format('Ymd_His') . '.txt');
    }
*/

    public function exportCompletedTrades(string $slug, string $trade_id, Request $request)
    {
        $model = AiModel::where('slug', $slug)->firstOrFail();            

        $trade = Trade::where('ai_model_id', $model->id)
                    ->where('id', $trade_id)
                    ->whereNotNull('closed_at')
                    ->orderByDesc('closed_at')                   
                    ->firstOrFail();

        $aiDailyPlan = AiDailyPlan::where('ai_model_id', $model->id)      
                        ->where('trade_date', $trade->date)
                        ->first();

        $content = "=== Completed Trade Export ===\n";
        $content .= "Export Date: " . now() . "\n\n";

        $content .= "Trade ID: {$trade->id}\n";
        $content .= "Symbol: {$trade->ticker} - {$trade->side}\n";
        $content .= "Qty: {$trade->qty}\n";
        $content .= "Entry Price: \${$trade->entry_price}\n";
        $content .= "Exit Price: \${$trade->exit_price}\n";
        $content .= "Net PNL: \${$trade->net_pnl}\n";
        $content .= "Completed At: {$trade->closed_at}\n";
        $content .= "Exit Reason: {$trade->exit_reason_text}\n";

        if ($aiDailyPlan && isset($aiDailyPlan->locked_loop_prompt)) {
            $content .= "\n=== Loop Prompt ===\n";
            $content .= json_encode($aiDailyPlan->locked_loop_prompt, JSON_PRETTY_PRINT);
            $content .= "\n";
        }

        if ($aiDailyPlan && isset($aiDailyPlan->locked_premarket_prompt)) {
            $content .= "\n=== Pre-market Prompt ===\n";
            $content .= json_encode($aiDailyPlan->locked_premarket_prompt, JSON_PRETTY_PRINT);
            $content .= "\n";
        }

        $content .= "\n=== End of Export ===\n";

        $fileName = 'completed_trade_' . now()->format('Ymd_His') . '.txt';

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $fileName);
    }

}
