<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\AiModel;
use App\Models\ModelLog;
use App\Models\Trade;
use App\Models\EquitySnapshot;
use App\Services\AiDecisionParser;
use Carbon\Carbon;

class AiTick extends Command
{
   protected $signature = 'ai:tick';
   protected $description = 'Runs one tick for all active AI trading models';
   public function handle()
   {
       $now = Carbon::now();
       $models = AiModel::where('active', true)->get();
       foreach ($models as $model) {
           try {
               // ------------------------------
               // 1) Respect check interval
               // ------------------------------
               if (!empty($model->last_checked_at)) {
                   $last = $model->last_checked_at instanceof Carbon
                       ? $model->last_checked_at
                       : Carbon::parse($model->last_checked_at);
                   $interval = $model->check_interval ?? $model->check_interval_min ?? 1; // fallback
                   $nextCheck = $last->copy()->addMinutes($interval);
                   if ($now->lt($nextCheck)) {
                       // Skip this model this minute
                       continue;
                   }
                }
               $this->info("Ticking model: {$model->name}");
               // ------------------------------
               // 2) Build AI prompt
               // ------------------------------
               $systemPrompt = <<<TXT
You are an autonomous trading agent.
Always respond ONLY with valid JSON:
{
 "action": "HOLD" | "OPEN" | "CLOSE",
 "strategy": "string (short label)",
 "reasoning": "string",
 "orders": [
     {
        "symbol": "AAPL",
        "side": "BUY" | "SELL",
        "qty": 10,
        "type": "MARKET"
     }
 ]
}
TXT;
               $userPrompt = $model->loop_prompt;
               // ------------------------------
               // 3) CALL THE RESPONSES API HERE
               // ------------------------------
               // TODO: integrate your actual Responses API client:
               // $outputText = app(ResponsesClient::class)->send($systemPrompt, $userPrompt);
               // For now: dummy placeholder
               $outputText = '{"action":"HOLD","strategy":"idle","reasoning":"Waiting","orders":[]}';
               // ------------------------------
               // 4) Parse JSON safely
               // ------------------------------
               $decision = AiDecisionParser::parse($outputText);
               // ------------------------------
               // 5) Save log (Model Chat uses this)
               // ------------------------------
               $log = new ModelLog();
               $log->ai_model_id  = $model->id;
               $log->action       = $decision['action'];
               // $log->strategy     = $decision['strategy'];
               // $log->thoughts     = $decision['reasoning'];
               // $log->raw_response = $decision['raw_json'];
               // $log->raw_request  = $userPrompt;

               $log->payload = [
                   'strategy'  => [
                       'name' => $decision['strategy'],
                   ],
                   'reasoning' => $decision['reasoning'],
                   'orders'    => $decision['orders'],
                   'raw'       => [
                       'response' => json_decode($decision['raw_json'], true),
                       'prompt'   => $userPrompt,
                   ],
                ];
               $log->save();
               // ------------------------------
               // 6) Execute orders (OPEN/CLOSE)
               // ------------------------------
               if (in_array($decision['action'], ['OPEN', 'CLOSE'])) {
                   foreach ($decision['orders'] as $order) {
                       $symbol = $order['symbol'] ?? null;
                       $side   = strtoupper($order['side'] ?? 'BUY');
                       $qty    = (float)($order['qty'] ?? 0);
                       if (! $symbol || $qty <= 0) {
                           continue;
                       }
                       $trade = new Trade();
                       $trade->ai_model_id = $model->id;
                       $trade->symbol      = $symbol;
                       $trade->side        = $side;
                       $trade->qty         = $qty;
                       $trade->status      = 'open';
                       $trade->opened_at   = now();
                       $trade->entry_price = 0; // replace with real feed/webhook price
                       $trade->save();
                   }
               }
               // ------------------------------
               // 7) Update equity + snapshot
               // ------------------------------
               // TODO: update $model->equity based on trades / PnL logic
               EquitySnapshot::create([
                   'ai_model_id' => $model->id,
                   'equity'      => $model->equity,
                   'taken_at'    => now(),
               ]);
               $model->last_checked_at = now();
               $model->save();
           } catch (\Throwable $e) {
               $this->error("Error in model {$model->name}: ".$e->getMessage());
               // Important: log crash to DB (so UI can show failure)
               // ModelLog::create([
               //     'ai_model_id'  => $model->id,
               //     'action'       => 'ERROR',
               //     'strategy'     => null,
               //     'thoughts'     => $e->getMessage(),
               //     'raw_response' => null,
               //     'raw_request'  => null,
               // ]);

               ModelLog::create([
                   'ai_model_id' => $model->id,
                   'action'      => 'ERROR',
                   'payload'     => [
                       'error' => $e->getMessage(),
                   ],
                ]);
               continue;
           }
       }
       return self::SUCCESS;
   }
}
