<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AiModel;
use App\Models\ModelLog;
use App\Services\ResponsesClient;
use App\Services\RiskManager;
use App\Services\PaperBroker;
use App\Services\AutoStopTP;
use App\Services\PortfolioService;
use App\Services\Guards\Guardrails;

use Carbon\Carbon;

class AiTick extends Command
{
    protected $signature = 'ai:tick {--model=}';
    protected $description = 'Run one loop tick for active AI models (or a specific one).';

    public function handle(
        ResponsesClient $client,
        RiskManager $risk,
        PaperBroker $broker,
        AutoStopTP $auto,
        PortfolioService $portfolio,
        Guardrails $guards
    )
    {
        $query = AiModel::where('active', true);
        if ($slug = $this->option('model')) $query->where('slug', $slug);

        $models = $query->get();
        if ($models->isEmpty()) { $this->info('No active models found.'); return Command::SUCCESS; }

        foreach ($models as $m) {
            if ($m->last_checked_at) {
                $due = now()->subMinutes($m->check_interval_min ?? 1);
                //if ($m->last_checked_at->gt($due)) { $this->info("[{$m->slug}] skip (interval)"); continue; }
                if (Carbon::parse($m->last_checked_at)->gt($due)) {
                    $this->info("[{$m->slug}] skip (interval)");
                    continue;
                }
            }

            $state = ['equity'=>$m->equity, 'clock'=>now()->toIso8601String()];

            $decision = $client->loop($m->loop_prompt ?: 'Decide what to do.', $state);

            // Basic schema validation
            $required = ['action','orders','strategy','reasoning'];
            $missing = [];
            foreach ($required as $k) if (!array_key_exists($k, $decision)) $missing[]=$k;
            if (!empty($missing)) {
                ModelLog::create([
                    'ai_model_id'=>$m->id,'action'=>'HOLD',
                    'payload'=>['error'=>'invalid_response','missing'=>$missing,'raw'=>$decision]
                ]);
                $this->warn("[{$m->slug}] Invalid AI response: missing ".implode(',',$missing));
                $m->last_checked_at = now(); $m->save();
                continue;
            }

            // Guardrails
            [$ok, $violations, $computed] = $guards->validate($m, $decision);
            if (!$ok) {
                ModelLog::create([
                    'ai_model_id'=>$m->id,'action'=>'HOLD',
                    'payload'=>['guardrails'=>'blocked','violations'=>$violations,'computed'=>$computed,'decision'=>$decision]
                ]);
                $this->info("[{$m->slug}] HOLD (guardrails): ".implode(';',$violations));
                $m->last_checked_at = now(); $m->save();
                continue;
            }

            // Risk sizing
            $orders = $decision['orders'] ?? [];
            foreach ($orders as &$o) { $o = $risk->size($m, $o); } unset($o);

            if (!empty($orders)) $broker->execute($m, $orders);

            $auto->run($m);
            $portfolio->markToMarket($m);

            ModelLog::create([
                'ai_model_id'=>$m->id,'action'=>$decision['action'] ?? 'n/a',
                'payload'=>$decision + ['computed'=>$computed]
            ]);

            $m->last_checked_at = now();
            if ($m->equity !== null && ($m->peak_equity ?? 0) < $m->equity) $m->peak_equity = $m->equity;
            $m->save();

            $this->info("[{$m->slug}] action=".($decision['action'] ?? 'n/a'));
        }

        return Command::SUCCESS;
    }
}
