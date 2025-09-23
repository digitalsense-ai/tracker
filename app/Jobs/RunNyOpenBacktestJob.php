<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;
use App\Services\NyOpenBacktester;

class RunNyOpenBacktestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $dateYmd,
        protected array $universe,
        protected array $profiles,
        protected bool $dryRun = false
    ) {}

    public function handle(): void
    {
        $cfg = config('nyopen');
        $tzEt = $cfg['tz_market']; // America/New_York
        $windowStartEt = $cfg['window_start']; // 09:30:00
        $windowEndEt   = $cfg['window_end'];   // 11:30:00

        $service = app(NyOpenBacktester::class);

        // Resolve profiles
        $profilesToRun = $this->profiles;
        if (empty($profilesToRun)) {
            $profilesToRun = DB::table('strategy_profiles')
                ->where('enabled', 1)
                ->orderBy('name')
                ->pluck('name')
                ->all();
        }

        foreach ($profilesToRun as $pname) {
            $pid = DB::table('strategy_profiles')->where('name', $pname)->value('id');
            if (!$pid) { Log::warning("NYOPEN: profile not found: {$pname}"); continue; }

            foreach ($this->universe as $ticker) {
                $startEt = CarbonImmutable::parse($this->dateYmd . ' ' . $windowStartEt, $tzEt);
                $endEt   = CarbonImmutable::parse($this->dateYmd . ' ' . $windowEndEt,   $tzEt);

                $trades = $service->runProfileOnTickerWindow($pid, $ticker, $startEt, $endEt);

                if ($this->dryRun) {
                    Log::info("NYOPEN DRY: {$pname} {$ticker} -> trades=" . count($trades));
                    continue;
                }

                foreach ($trades as $t) {
                    DB::table('trades')->updateOrInsert(
                        [
                            'strategy_profile_id' => $pid,
                            'ticker' => $ticker,
                            'created_at' => $t['created_at'], // entry time UTC
                        ],
                        [
                            'entry_price' => $t['entry_price'],
                            'exit_price'  => $t['exit_price'],
                            'closed_at'   => $t['closed_at'] ?? null,
                            'forecast_type' => $t['forecast_type'] ?? 'gap-up',
                            'market_session' => 'ny_open',
                            'updated_at' => now('UTC'),
                        ]
                    );
                }
            }
        }
    }
}
