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
        PortfolioService $portfolio
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

            $state = [
                'equity' => $m->equity,
                'open_positions' => [],
                'recent_trades' => [],
                'indicators' => [],
                'clock' => now()->toIso8601String(),
            ];

            $decision = $client->loop($m->loop_prompt ?: 'Decide what to do.', $state);
            $action = $decision['action'] ?? null;
            $orders = $decision['orders'] ?? [];

            foreach ($orders as &$o) { $o = $risk->size($m, $o); }
            unset($o);

            if (!empty($orders)) $broker->execute($m, $orders);

            $auto->run($m);
            $portfolio->markToMarket($m);

            ModelLog::create([ 'ai_model_id' => $m->id, 'action' => $action, 'payload' => $decision ]);

            $m->last_checked_at = now();
            $m->save();

            $this->info("[{$m->slug}] action=".($action ?? 'n/a'));
        }

        return Command::SUCCESS;
    }
}
